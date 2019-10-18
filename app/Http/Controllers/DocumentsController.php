<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Lumen\Routing\Router;

class DocumentsController extends Controller
{

    /**
     * Store a documents in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadDocs(Request $request)
    {
        $this->validate($request,[
            'title' => 'required',
            'document' => 'required|mimes:jpeg,bmp,png'
        ]);

        $imageName = time().'.'.$request->document->getClientOriginalExtension();

        $isUploaded = $request->document->move(storage_path('app/public/documents'), $imageName);

        $fullyQualifiedPath = '/storage/documents/'.$imageName;


        if(!$isUploaded)
        {
            return response()->json(['status'=>'error', 'message'=>'Oops! something went wrong. Please re-upload document.'], 400);
        }

        $docCreated = Document::create([
            'tutor_id'          => Auth::user()->id,
            'title'             => $request->title,
            'path'              => $fullyQualifiedPath,
            'status'            => 2,
            'verified_by'       => null,
            'verified_at'       => null,
            'rejection_reason'  => null
        ]);


        if(!$docCreated)
        {
            unlink(storage_path('app/public/documents').'/'. $imageName);
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
            'message'   =>  'Document found successfully!',
            'documents' =>  $documents
        ], 200);
    }

    public function deleteTutorsDoc($documentId){

        $document = Document::find($documentId);
        if(!$document)
            return response()->json([
                'status'=>'error',
                'message'=>'Document does not exists.'
            ], 400);

        unlink($document->path);

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

}
