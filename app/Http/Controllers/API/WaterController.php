<?php

namespace App\Http\Controllers\API;

use App\Models\Water;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\WaterResource;
use Illuminate\Support\Facades\Validator;


class WaterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // bisa diperbarui untuk dapat menerapkan pagination dan filtering berdasarkan code github sebelumnya 
        // $waters = Water::latest()->get();
        $waters = Water::all();
        return response()->json([
            'data' => WaterResource::collection($waters),
            'message' => 'Fetch all posts',
            'success' => true
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'regency_id' => 'required|exists:regencies,regency_id',
            'lu_id.*' => 'required',
            'lc_id' => 'required|exists:land_covers,lc_id',
            'group_id' => 'required|exists:spatial__groups,group_id',
            'name' => 'required|max:45',
            'latitude' => 'required|max:45',
            'longitude' => 'required|max:45',
            'altitude' => 'required|max:45',
            'address' => 'required',
            'wide' => 'required|max:45',
            'aoi' => 'required',
            'status_area' => 'required|in:private,public',
            'ownership' => 'required|max:45',
            'photo' => 'required',
            'permanence' => 'required|max:100',
            'description' => 'required',
            'related_photo' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [],
                'message' => $validator->errors(),
                'success' => false
            ]);
        }


        $water = new Water($request->all());
        $water->lu_id = $request->lu_id;
        // Memastikan bahwa lu_id tersimpan sebagai array
        // $water->lu_id = json_encode($request->lu_id);

        // Handling photo and related_photo
        $water->photo = $this->storeFile($request->file('photo'), 'water-photo');
        $water->related_photo = $this->storeFile($request->file('related_photo'), 'water-related-photo');

        $water->save();

        return response()->json([
            'data' => new WaterResource($water),
            'message' => 'Post created successfully.',
            'success' => true
        ]);
    }


    private function storeFile($file, $storagePath)
    {
        if ($file) {
            $originalName = $file->getClientOriginalName();
            return $file->storeAs($storagePath, $originalName);
        }

        return null;
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Water  $water
     * @return \Illuminate\Http\Response
     */



    public function show(Water $water)
    {
        try {
            $resource = new WaterResource($water);
            if ($resource->water_id === null) {
                throw new \Exception('ID is null');
            }

            return response()->json([
                'data' => $resource,
                'message' => 'Data Water found',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Water  $water
     * @return \Illuminate\Http\Response
     */


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Water  $water
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Water $water)
    {
        $validator = Validator::make($request->all(), [
            'regency_id' => 'sometimes|exists:regencies,regency_id',
            'lu_id.*' => 'sometimes|required',
            'lc_id' => 'sometimes|exists:land_covers,lc_id',
            'group_id' => 'sometimes|required|exists:spatial__groups,group_id',
            'name' => 'sometimes|required|max:45',
            'latitude' => 'sometimes|required|max:45',
            'longitude' => 'sometimes|required|max:45',
            'altitude' => 'sometimes|required|max:45',
            'address' => 'sometimes|required',
            'wide' => 'sometimes|required|max:45',
            'aoi' => 'sometimes|required',
            'status_area' => 'sometimes|required|in:private,public',
            'ownership' => 'sometimes|required|max:45',
            'permanence' => 'sometimes|required|max:100',
            'description' => 'sometimes|required',
            'photo' => 'sometimes|image', // Assuming photo is an image file
            'related_photo' => 'sometimes|image', // Assuming related_photo is an image file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [],
                'message' => $validator->errors(),
                'success' => false
            ]);
        }

        // Handling photo and related_photo
        $photo = $request->file('photo');
        $relatedPhoto = $request->file('related_photo');
        // Update only the fields that are present in the request
        $dataToUpdate = $request->only([
            'regency_id',
            'lu_id',
            'lc_id',
            'name',
            'latitude',
            'longitude',
            'altitude',
            'address',
            'wide',
            'aoi',
            'status_area',
            'ownership',
            'permanence',
            'description',
        ]);

        // Update related_photo only if a new file is provided
        if ($relatedPhoto) {
            if ($water->related_photo && file_exists(storage_path('app/' . $water->related_photo))) {
                unlink(storage_path('app/' . $water->related_photo));
            }
            $dataToUpdate['related_photo'] = $this->storeFile($relatedPhoto, 'water-related-photo');
        }

        // Update photo only if a new file is provided
        if ($photo) {
            if ($water->photo && file_exists(storage_path('app/' . $water->photo))) {
                unlink(storage_path('app/' . $water->photo));
            }
            $dataToUpdate['photo'] = $this->storeFile($photo, 'water-photo');
        }

        $water->update($dataToUpdate);

        return response()->json([
            'data' => new WaterResource($water),
            'message' => 'Water updated successfully',
            'success' => true
        ]);
    }








    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Water  $water
     * @return \Illuminate\Http\Response
     */
    public function destroy(Water $water)
    {
        $water->delete();

        return response()->json([
            // 'data' => [],
            'message' => 'Water deleted successfully',
            'success' => true
        ]);
    }
}