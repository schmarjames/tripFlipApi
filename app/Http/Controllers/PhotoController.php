<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Likes;
use App\User;
use App\Tfphotos;

class PhotoController extends Controller
{
  protected $weatherApiKey = "dc917ecc31f3df833231b3804d609fed";
  protected $message = [
    "error" => [
      "get_likes" => "Sorry, unable to get the list of people who've liked this photo",
      "get_country_photos" => "Sorry, unable to get the countries photos"
    ]
  ];

  public function __construct() {
      \Config::set('auth.model', 'App\User');
      $this->middleware('jwt.auth');
  }

    public function getSpecificLikes($photo_id) {
      if (!is_numeric($id) || is_null($id)) return response()->json($this->message["error"]["get_likes"]);

      $users = Likes::where('photo_id', $photo_id)->select('user_id')->get()->toArray();
      $likeArr = User::find($user)->select(array('id', 'name', 'profile_pic'))->get();
      return response()->json($likeArr);
    }

    public function addViews($arr) {
      $photosArr = unserialize($arr);

      if (count($photosArr) > 0) {
        foreach($photosArr as $photo_id) {
          $photo = Tfphotos::find($photo_id);
          $photo->view = $photo->view + 1;
          $photo->save();
        }
        return true;
      }
      return false;
    }

    public function addLikes($id, $like) {

      // check user id
      $user = User::find($id);

      // Check if $likes is an array
      if (!is_null($user)) {
        if ($likes_arr = is_array($like)) {
          $likes_arr = unserialize($likes_arr);

          foreach ($likes_arr as $like) {
            dd($like);
            Likes::create([
              'user_id' => $id,
              'photo_id' => $like
            ]);
          }
          return response()->json(1);
        }

        Likes::create([
          'user_id' => $id,
          'photo_id' => $like
        ]);
        return response()->json(1);
      }

      return response()->json(["error" => "Unauthorized users cannot use this functionality."]);
    }

    public function getCollection(Request $request) {
      $data = $request->only('amount', 'lastQueryId', 'latest');

      // If adding more photos to feed
      if (is_numeric($data['lastQueryId']) && !(bool)$data['latest']) {
        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->where('tfphotos.id', '<', $data['lastQueryId'])
          ->take($data['amount'])
          ->orderBy('created_at', 'desc')
          ->get();
      }
      // If adding latest photos to feed
      else if (is_numeric($data['lastQueryId']) && (bool)$data['latest']) {
        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->where('tfphotos.id', '>', $data['lastQueryId'])
          ->orderBy('created_at', 'asc')
          ->get();
      }
      // If initial query for feed
      else {
          $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
            ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
            ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
            ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
            ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
            ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
            ->take($data['amount'])
            ->orderBy('created_at', 'desc')
            ->get();
      }

      // include the likes and weather data foreach photo
      $collection = $collection->map(function($photo, $v) {
        $photo['likes'] = Likes::where("photo_id", $photo->id)->select("user_id")->get()->count();
        $photo["weatherCondition"] = $this->getWeatherData($photo["lat"], $photo["long"])["weather"][0]["description"];

        return $photo;
      });
      // return collection of photos and last photos id
      return response()->json($collection);
    }

    public function getUpdatePhotoFeedCount(Request $request) {
      $data = $request->only('lastQueryId');

      if (!is_numeric($data['lastQueryId']) || is_null($data['lastQueryId'])) { return response()->json(["message" => "Invalid id was given."]); }
      $total = Tfphotos::where('id', '>', $data['lastQueryId'])
        ->orderBy('created_at', 'asc')
        ->get()
        ->count();

      return response()->json($total);
    }

    public function getWeatherData($lat, $long) {
      //api.openweathermap.org/data/2.5/weather?lat=" + this.state.lat + "&lon=" + this.state.long + "&APPID=dc917ecc31f3df833231b3804d609fed"
      $url = sprintf(
        "api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&APPID=%s",
        $lat,
        $long,
        $this->weatherApiKey
      );

      $client = new \GuzzleHttp\Client();
      $response = $client->get($url);

      if ($response->getStatusCode() == 200) {
          $res_data = $response->getBody()->getContents();
          return json_decode($res_data, true);
      }
      return false;
    }
}
