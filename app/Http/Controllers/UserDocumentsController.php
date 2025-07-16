<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDocument;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Http\Requests\UserDocuments\StoreUserDocumentsRequest;

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

            $documents = $request->validated();

            $savedDocuments = [];

            foreach ($documents as $document) {
                $file = $document['resource'];

                $result = Cloudinary::uploadFile($file->getRealPath(), [
                    'folder' => 'user_documents/' . $user->id,
                    'resource_type' => 'auto',
                ]);

                $secureUrl = $result->getSecurePath();

                $saved = UserDocument::create([
                    'user_id' => $user->id,
                    'name' => $document['name'],
                    'resource_url' => $secureUrl,
                ]);

                $savedDocuments[] = $saved;
            }

            return ApiResponse::success('User documents uploaded and saved successfully.', $savedDocuments);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
