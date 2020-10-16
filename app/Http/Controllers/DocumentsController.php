<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Document;
use App\Models\Programme;
use App\Models\ProgramSubject;
use App\Models\Subject;
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
        if ($request->has("device")){
            $this->validate($request,[
                'title'             =>  'required',
                'document'          =>  'required',
                'document_type'     =>  'required'
            ]);
        } else {
            $this->validate($request,[
                'title'             =>  'required',
                'document'          =>  'required|mimes:jpeg,bmp,png|min:1024',
                'document_type'     =>  'required'
            ]);
        }

        $tutorId = Auth::user()->id;
        // CNIC Front & Back
        if ($request->document_type == "cnic_front" || $request->document_type == "cnic_back"){

            if ($request->title == "CNIC Front" || $request->title == "Front Side"){
                $cnicSide = 'cnic_front';
            }
            if ($request->title == "CNIC Back" || $request->title == "Back Side"){
                $cnicSide = 'cnic_back';
            }
            $cnicProgram = Programme::where('name', '=', 'Cnic')->first();
            $cnicSubject = Subject::where('programme_id', $cnicProgram->id)->where('name', '=', $cnicSide)->first();
            $docCreatedId = $this->createDocument($request, $tutorId);
            ProgramSubject::create([
                'program_id' => $cnicProgram->id,
                'subject_id' => $cnicSubject->id,
                'user_id' => Auth::user()->id,
                'document_id' => $docCreatedId,
                'status' => 2,
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null
            ]);

            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Document uploaded successfully!'
            ], 201);
        }
        // Profile Photo
        elseif ($request->document_type == "profile_photo"){
            $profilePhotoProgram = Programme::where('name', '=', 'ProfilePhoto')->first();
            $profilePhotoSubject = Subject::where('programme_id', $profilePhotoProgram->id)->first();
            $docCreatedId = $this->createDocument($request, $tutorId);
            ProgramSubject::create([
                'program_id' => $profilePhotoProgram->id,
                'subject_id' => $profilePhotoSubject->id,
                'user_id' => Auth::user()->id,
                'document_id' => $docCreatedId,
                'status' => 2,
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null
            ]);
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Profile Photo uploaded successfully!'
            ], 201);
        }
        // Documents
        else {
            $programId = Programme::where('name', '=', $request->title)->first();
            $docCreatedId = $this->createDocument($request, $tutorId);
            $tutorClassesSubjects = ProgramSubject::where('user_id', $tutorId)->where('program_id', $programId->id)->get();
            //update document Id against program subject
            if ($tutorClassesSubjects){
                foreach ($tutorClassesSubjects as $classesSubject){
                    ProgramSubject::where('id', $classesSubject->id)->update(['document_id' => $docCreatedId]);
                }
            }
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Document uploaded successfully!'
            ], 201);
        }
    }

    public function createDocument($request, $tutorId){
        $response = $this->uploadDocumentImage($request);
        $docCreatedId = Document::create([
            'tutor_id'          => $tutorId,
            'title'             => $request->title,
            'path'              => $response['accessPath'],
            'document_type'     => $request->document_type,
            'storage_path'      => $response['storagePath'],
        ])->id;

        if(!$docCreatedId)
        {
            unlink($response['storagePath']);
            return response()->json([
                'status'    =>  'error',
                'message'   =>  'Oops! Something went wrong! Please re-upload the document'
            ], 400);
        }

        return $docCreatedId;
    }


    /**
     * Listing tutor's documents.
     */
    public function tutorsDocsList()
    {
        $tutorId = Auth::user()->id;
//        $documents = Document::where('tutor_id', $tutorId)->get();
        $programSubjects = ProgramSubject::where('user_id', $tutorId)->with('program', 'subject', 'document')->get();
        $documents = [];
        foreach($programSubjects as $programSubject){
            if ($programSubject->document != null){
                $data = [];
                $data['id'] = $programSubject->document->id;//important to use document id here
                $data['title'] = $programSubject->program->name.'('.$programSubject->subject->name.')';
                $data['tutor_id'] = $tutorId;
                $data['path'] = $programSubject->document->path;
                $data['status'] = $programSubject->status;
                $data['rejection_reason'] = $programSubject->rejection_reason;
                $data['document_type'] = $programSubject->document->document_type;
                $documents[] = $data;
            }
        }

//        $cnicDocuments = Document::where('tutor_id',$tutorId)->where('document_type','like','cnic_%')->get();
//        $documents = array_merge($documents,$cnicDocuments->toArray());


        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Documents found successfully!',
            'documents' =>  $programSubjects
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
        if ($request->has("device")){
            $this->validate($request, [
                'document_id'       =>  'required',
                'title'             =>  'required',
                'document'          =>  'required|mimes:jpeg,bmp,png',
                'document_type'     =>  'required'
            ]);
        } else {
            $this->validate($request, [
                'document_id'       =>  'required',
                'title'             =>  'required',
                'document'          =>  'required|mimes:jpeg,bmp,png|min:1024',
                'document_type'     =>  'required'
            ]);
        }

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
        ]);

        //Update in Program_subject table for program and subjects if status is rejected than update
            $programSubjects = ProgramSubject::where('document_id', $documentId)->where('status', 0)->get();
            if (!empty($programSubjects)){
                foreach($programSubjects as $programSubject) {
                    ProgramSubject::where('id', $programSubject->id)->update([
                        'status' => 2,
                        'verified_by' => null,
                        'verified_at' => null,
                        'rejection_reason' => null
                    ]);
                }
        } else {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  "Document can't be uploaded as it's already in review!"
                ]);
        }

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

    public function allDocumentsSubmitted(Request $request) {
        $userId = Auth::user()->id;
        if ($userId) {
            User::where('id', $userId)->update([
                'is_documents_uploaded' => 1
            ]);
            return response()->json([
                'status'    =>  'success',
                'message'   =>  "All documents uploaded successfully"
            ]);
        } else {
            return response()->json([
                'status'    =>  'error',
                'message'   =>  "Some thing went wrong No user found"
            ]);
        }
    }
}
