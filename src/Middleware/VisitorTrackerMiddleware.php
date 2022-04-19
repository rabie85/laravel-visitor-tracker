<?php

namespace Drsoft\VisitorTracker\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tracker;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Route;
class VisitorTrackerMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $expect_routes = config('visitor-tracker.routes');

        if($expect_routes && is_array($expect_routes)){
            $ok = true;
            foreach ($expect_routes as  $value) {
                $ok= ! request()->routeIs($value);
                if(!$ok) break; 
            }
            if(!$ok) $next($request);
        }

        $expect_paths = config('visitor-tracker.paths');

        if($expect_paths && is_array($expect_paths)){
            $ok = true;
            foreach ($expect_paths as  $value) {
                $ok= ! request()->is($value);
                if(!$ok) break; 
            }
            if(!$ok) $next($request);
        }
        $request_info=[];
        $headers = config('visitor-tracker.headers');

        if($headers && is_array($headers)){
            
            foreach ($headers as  $value) {
                $request_info[$value] =$request->server($value);
            }
           
        }
            
        
        $position = Location::get(request()->ip());
        $route = Route::getRoutes()->match($request);
            $route_name = null;
            $route_params = null;
        if($route){
            $route_name = $route->getName();
            $route_params = $route->parameters();
        }
        
        $model = config('visitor-tracker.model');

        $tracker = new $model();
        $tracker->user_id = auth()->check()?auth()->user()->id:null;
        $tracker->path = $request->path();
        $tracker->url = $request->url();
        $tracker->full_url = $request->fullUrl();
        $tracker->ip = $request->ip();
        $tracker->route_name = $route_name;
        $tracker->route_params = $route_params?json_encode( $route_params):null;
        $tracker->request_info = json_encode( $request_info);
        $tracker->country_name=optional($position)->countryName;
        $tracker->country_code=optional($position)->countryCode;
        $tracker->region_code=optional($position)->regionCode;
        $tracker->region_name=optional($position)->regionName;
        $tracker->city_name=optional($position)->cityName;
        $tracker->zip_code=optional($position)->zipCode;
        $tracker->iso_code=optional($position)->isoCode;
        $tracker->postal_code=optional($position)->postalCode;
        $tracker->latitude=optional($position)->latitude;
        $tracker->longitude=optional($position)->longitude;
        $tracker->metro_code=optional($position)->metroCode;
        $tracker->timezone=optional($position)->timezone;
        $tracker->referer= request()->headers->get('referer');;
        $tracker->save();
        $tracker->refresh();
        $request->merge(['visitor_tracker_table_id'=>$tracker->getKey()]);
        
        return $next($request);
    }
}
