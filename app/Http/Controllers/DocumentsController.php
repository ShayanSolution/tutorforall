<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentsController extends Controller
{
    protected static $documentStoragePath;

    public function __construct()
    {
        self::$documentStoragePath  = storage_path('app/public/documents');
    }

    /**
     * Store a documents in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadDocs(Request $request)
    {
        $this->validate($request,[
            'title'             =>  'required',
            'document'          =>  'required|mimes:jpeg,bmp,png',
            'document_type'     =>  'required'
        ]);

        $response = $this->uploadDocumentImage($request);

        $docCreated = Document::create([
            'tutor_id'          => Auth::user()->id,
            'title'             => $request->title,
            'path'              => $response['accessPath'],
            'document_type'     => $request->document_type,
            'storage_path'      => $response['storagePath'],
            'status'            => 2,
            'verified_by'       => null,
            'verified_at'       => null,
            'rejection_reason'  => null
        ]);


        if(!$docCreated)
        {
            unlink($response['storagePath']);
            return response()->json([
                'status'    =>  'error',
                'message'   =>  'Oops! Something went wrong! Please re-upload the document'
            ], 400);
        }

        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Document uploaded successfully!'
        ], 201);


    }


    /**
     * Listing tutor's documents.
     */
    public function tutorsDocsList()
    {
        $tutorId = Auth::user()->id;
        $documents = Document::where('tutor_id', $tutorId)->get();

        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Documents found successfully!',
            'documents' =>  $documents
        ], 200);
    }


    /**
     * Delete Tutor's Documents.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteTutorsDoc(Request $request){

        $documentId = $request->document_id;

        $document = Document::find($documentId);

        if(!$document){
            return response()->json([
                'status'=>'error',
                'message'=>'Document does not exists.'
            ], 400);
        }

        unlink($document->storage_path);

        $isdeleted = $document->delete();

        if(!$isdeleted)
            return response()->json([
                'status'=>'error',
                'message'=>'Document not deleted.'
            ], 409);

        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Document deleted successfully!'
        ], 200);

    }


    /**
     * Delete Tutor's Documents.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTutorsDoc(Request $request){

        $this->validate($request, [
            'document_id'       =>  'required',
            'title'             =>  'required',
            'document'          =>  'required|mimes:jpeg,bmp,png',
            'document_type'     =>  'required'
        ]);

        $documentId = $request->document_id;

        $document = Document::find($documentId);

        if(!$document)
            return response()->json([
                'status'    =>  'error',
                'message'   =>  'Document does not exists!'
            ], 404);

        $deletedOriginalDocImage = unlink($document->storage_path);

        if(!$deletedOriginalDocImage)
            return response()->json([
                'status'    =>  'error',
                'message'   =>  'Oops! Something went wrong'
            ], 409);

        $response = $this->uploadDocumentImage($request);

        $document->update([
            'tutor_id'          => Auth::user()->id,
            'title'             => $request->title,
            'path'              => $response['accessPath'],
            'document_type'     => $request->document_type,
            'storage_path'      => $response['storagePath'],
            'status'            => 2,
            'verified_by'       => null,
            'verified_at'       => null,
            'rejection_reason'  => null
        ]);

        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Document updated successfully!'
        ]);
    }


    private function uploadDocumentImage($request){

        $imageName = time().'.'.$request->document->getClientOriginalExtension();

        $isUploaded = $request->document->move(self::$documentStoragePath, $imageName);

        $storagePath = self::$documentStoragePath.'/'.$imageName;

        $fullyQualifiedPath = '/storage/documents/'.$imageName;

        if(!$isUploaded)
            return response()->json(['status'=>'error', 'message'=>'Oops! something went wrong. Please re-upload document.'], 400);

        return [
            'accessPath'    =>  $fullyQualifiedPath,
            'storagePath'   =>  $storagePath
        ];
    }
}