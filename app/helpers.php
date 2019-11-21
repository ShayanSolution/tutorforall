<?php
/**
 * Created by PhpStorm.
 * User: NSC
 * Date: 11/21/2019
 * Time: 8:24 PM
 */

if (! function_exists('create_queued_job_tracking')) {
    function create_queued_job_tracking($jobId, $modelClassName, $id) {
        \DB::table('queued_job_tracking')->insert(
            [
                'queued_job_id'     =>  $jobId,
                'model_class_name'  =>  $modelClassName,
                'model_id'          =>  $id
            ]
        );
    }
}
if (! function_exists('get_queued_job_ids')) {
    function get_queued_job_ids($modelName, $modelId) {
        return \DB::table('queued_job_tracking')
            ->where('model_class_name', $modelName)
            ->where('model_id', $modelId)
            ->pluck('queued_job_id');
    }
}
if (! function_exists('delete_queued_job_tracking')) {
    function delete_queued_job_tracking($params) {
        \DB::table('queued_job_tracking')->where($params)->delete();
    }
}
if (! function_exists('delete_queued_jobs')) {
    function delete_queued_jobs($ids) {
        \DB::table('jobs')->whereIn('id', $ids)->delete();
    }
}