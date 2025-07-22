<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDocument;
use App\Http\Requests\UserDocuments\StoreUserDocumentsRequest;
use Illuminate\Support\Facades\Log;

class UserDocumentsController extends Controller
{
    /**
     * Add user documents
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserDocumentsRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            $documents = $request->validated();
            if (!is_array($documents)) {
                throw new \Exception('Validated data is not an array.');
            }

            $savedDocuments = [];

            foreach ($documents as $index => $document) {
                if (!is_array($document) || !isset($document['resource'])) {
                    Log::warning("Skipping invalid document at index $index");
                    continue;
                }

                $file = $document['resource'];
                if (!$file || !$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
                    throw new \Exception('Invalid or missing file at index ' . $index . ': ' . ($file ? $file->getClientOriginalName() : 'No file'));
                }

                $result = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'user_documents/' . $user->id,
                    'resource_type' => 'auto',
                ]);

                if (!isset($result['secure_url'])) {
                    throw new \Exception('Invalid Cloudinary response: Missing secure_url');
                }

                $secureUrl = $result['secure_url'];

                $saved = UserDocument::create([
                    'user_id' => $user->id,
                    'name' => $document['name'],
                    'resource_url' => $secureUrl,
                ]);

                $savedDocuments[] = $saved;
            }

            $user->update(['reg_status' => 'review']);

            return ApiResponse::success('User documents uploaded and saved successfully.', $savedDocuments);
        } catch (\Throwable $th) {
            Log::error('Document upload error: ' . $th->getMessage(), ['trace' => $th->getTrace()]);
            return ApiResponse::error($th->getMessage());
        }
    }
}
