<?php

namespace App\Repositories;

use App\Models\SearchLocation;
use App\Repositories\Contracts\SearchLocationInterface;

class SearchLocationRepository implements SearchLocationInterface{

    public function searchLocation($request)
    {
        if ($request->modify_type == 'new') {
            $createSearchLocation = SearchLocation::create($request->all());
            return $createSearchLocation;
        } else {
            $id = $request->update_id;
            $updateSearchLocation = SearchLocation::where('id', $id)->update($request->except(['modify_type', 'update_id']));
            return $updateSearchLocation;
        }
    }

    public function locations($id)
    {
        $allLocations = SearchLocation::where('user_id', $id)->get();
        $searchLocations = [];
        foreach ($allLocations as  $location){
            if ($location->type == 'saved'){
                $saved = [];
                $saved['id'] = $location->id;
                $saved['user_id'] = $location->user_id;
                $saved['place_id'] = $location->place_id;
                $saved['place_name'] = $location->place_name;
                $saved['place_address'] = $location->place_address;
                $saved['place_detail'] = $location->place_detail;
                $saved['saved_name'] = $location->saved_name;
                $saved['latitude'] = $location->latitude;
                $saved['longitude'] = $location->longitude;
                $saved['type'] = $location->type;
                $saved['created_at'] = $location->created_at;
                $saved['updated_at'] = $location->updated_at;

                $searchLocations['saved_location'][] = $saved;
            } else {
                $recent = [];
                $recent['id'] = $location->id;
                $recent['user_id'] = $location->user_id;
                $recent['place_id'] = $location->place_id;
                $recent['place_name'] = $location->place_name;
                $recent['place_address'] = $location->place_address;
                $recent['place_detail'] = $location->place_detail;
                $recent['saved_name'] = $location->saved_name;
                $recent['latitude'] = $location->latitude;
                $recent['longitude'] = $location->longitude;
                $recent['type'] = $location->type;
                $recent['created_at'] = $location->created_at;
                $recent['updated_at'] = $location->updated_at;

                $searchLocations['recent_location'][]= $recent;
            }
        }
        return $searchLocations;
    }
}
