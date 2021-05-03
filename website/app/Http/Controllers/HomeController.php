<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Record;
use App\Models\Person;
use App\Models\User;

use Carbon\Carbon;

use Auth;
use Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function home()
    {
        $rows = Record::join('People', 'Records.person_id', '=', 'People.id')->orderBy('Records.timestamp', 'desc')->get();
        return view('dashboard', ['page_title' => 'Home', 'background' => 'bg-polygon-pattern', 'rows' => $rows]);
    }
    public function home_search()
    {
        $info=request()->all();
        
        $rows = Record::join('People', 'Records.person_id', '=', 'People.id');

        if(isset($info['from_date']))
        {    
            $rows=$rows->whereDate('timestamp', '>=', $info['from_date']);
            if(isset($info['from_time']))
            {
                $rows=$rows->where('timestamp', '>=', $info['from_date'].' '.$info['from_time'].':00');
            }
        }
        elseif(isset($info['from_time']))
            $rows=$rows->whereTime('timestamp', '>=', $info['from_time']);
        
        if(isset($info['to_date']))
        {
            $rows=$rows->whereDate('timestamp', '<=', $info['to_date']);
            if(isset($info['to_time']))
            {
               $rows=$rows->where('timestamp', '<=', $info['to_date'].' '.$info['to_time'].':00'); 
            }

        }
        elseif(isset($info['to_time']))
                $rows=$rows->whereTime('timestamp', '<=', $info['to_time']);    
            
        
        if(isset($info['gate']))
            $rows=$rows->where('gate', $info['gate']);
            
        if(isset($info['dept']))
            $rows=$rows->where('dept', $info['dept']);
        
        if(isset($info['level']))
            $rows=$rows->where('level', $info['level']);
        
        if(isset($info['mintemp']) && isset($info['maxtemp']))
            $rows=$rows->whereBetween('temperature', [ $info['mintemp'], $info['maxtemp'] ]);

        $str;
        if(isset($info['other_info']))
        {
            $GLOBALS['str']=$info['other_info'];
            $rows=$rows->where(function($query){
                $query->where('name', 'like', '%'.$GLOBALS['str'].'%')
                    ->orWhere('person_id', 'like', '%'.$GLOBALS['str'].'%')
                    ->orWhere('contact', 'like', '%'.$GLOBALS['str'].'%');
            });
        }

        
        $rows=$rows->orderBy('Records.timestamp', 'desc')->get();
        //dd($rows);
        return view('dashboard', ['page_title' => 'Home', 'background' => 'bg-polygon-pattern', 'rows' => $rows, 'info' => $info]);
    }

    public function alert_notification()
    {
        //$rows = Record::where('temperature', '>=', '100')->orderBy('updated_at', 'desc')->get();
        $rows = Record::join('People', 'Records.person_id', '=', 'People.id')->where('temperature', '>=', '100')->orderBy('Records.timestamp', 'desc')->get();;
        return view('alert_notification', ['page_title' => 'Notification', 'background' => 'bg-polygon-pattern', 'rows' => $rows]);
    }

    public function manage_account()
    {
        return view('manage_account', ['page_title' => 'Account', 'background' => 'bg-lace']);
    }

    public function update_account(Request $request)
    {
        //dd($request);
        if($request['email']!=$request['prev_email'])
        {
            $request->validate([
                'username' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:8'
            ]);
        }
        else
        {
            $request->validate([
                'username' => 'required',
                'email' => 'required|email',
                'password' => 'required|confirmed|min:8'
            ]);
        }

        $user = User::find(Auth::user()->id);
        
        if($user){
            $user->name = $request['username'];
            $user->email = $request['email'];
            $user->password = Hash::make($request['password']);
            $user->save();
            return redirect()->back();
        }

        abort(401);
    }

    // public function contact_us()
    // {
    //     return view('contact_us', ['page_title' => 'Contact Us', 'background' => 'bg-lace']);
    // }

    // public function about()
    // {
    //     return view('about', ['page_title' => 'About This Project', 'background' => 'bg-lace']);
    // }

    public function person($id)
    {
        $person = Person::where('id', $id)->first();
        if (!isset($person))
            abort(404);

        $entry_count= Record::where('person_id', $id)->count();
        $last_entry= Record::where('person_id', $id)->orderBy('timestamp', 'desc')->first()->timestamp;
        //dd($person);
        return view('person', ['page_title' => 'Person Details - '.$id, 'background' => 'bg-polygon-pattern', 'person' => $person, 'last_entry' => Carbon::parse($last_entry), 'entry_count' => $entry_count]);
    }
}
