<?php

namespace App\Http\Controllers;

use App\Models\TutorInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorInvoiceController extends Controller
{
    public function getTutorInvoice(){
        $tutorId = Auth::user()->id;
        $tutorInvoices = TutorInvoice::where('tutor_id', $tutorId)->get();
        if ($tutorInvoices) {
            return response()->json(
                [
                    'status' => 'success',
                    'invoices' => $tutorInvoices
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'No invoice found'
                ]
            );
        }
    }
    public function tutorPayInvoice(Request $request){
        $this->validate($request,[
            'invoice_id' => 'required',
            'transaction_ref_no' => 'required',
            'transaction_type' => 'required',
            'transaction_platform' => 'required',
            'transaction_status' => 'required',
        ]);
        $invoiceId = $request->invoice_id;
        $payInvoice = TutorInvoice::where('id', $invoiceId)->update([
            'status' => 'paid',
            'transaction_ref_no' => $request->transaction_ref_no,
            'transaction_type' => $request->transaction_type,
            'transaction_platform' => $request->transaction_platform,
            'transaction_status' => $request->transaction_status,
        ]);
        if ($payInvoice){
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Invoice paid successfully'
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invoice not paid'
                ]
            );
        }
    }
}
