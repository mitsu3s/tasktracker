<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user_id = Auth::user()->id;

        // Log::debug((session()->all()));
        $tasks = Task::where('user_id', $user_id)->where('begin', '>=', now())->orderBy('begin', 'asc')
            ->get();
        return view('dashboard', compact('tasks'));
    }

    public function create(Request $request)
    {
        $user_id = Auth::user()->id;

        $tasks = Task::where('user_id', $user_id)->get();
        return view('create', compact('tasks'));
    }

    public function store(Request $request)
    {
        // Log::debug($request->all());
        $request->validate([
            'context' => 'required',
            'place' => 'required',
            'begin' => 'required',
            'end' => 'required',
        ]);

        if (Auth::check()) {
            $task = new Task();
            $task->context = $request->context;
            $task->place = $request->place;
            $task->begin = $request->begin;
            $task->end = $request->end;
            $task->user_id = Auth::user()->id;

            $task->save();

            // return redirect('dashboard');
            return redirect()->route('dashboard');
        } else {

            // return redirect('login');
            return redirect()->route('login');
        }
    }

    public function edit($id)
    {
        $task = Task::find($id);
        // Log::debug($task);
        return view('edit', compact('task'));
    }

    public function update(Request $request, $id)
    {
        Log::debug($request->all());
        $request->validate([
            'context' => 'required',
            'place' => 'required',
            'begin' => 'required',
            'end' => 'required',
        ]);

        if (Auth::check()) {
            $task = Task::find($id);
            $task->context = $request->context;
            $task->place = $request->place;
            $task->begin = $request->begin;
            $task->end = $request->end;
            $task->user_id = Auth::user()->id;

            $task->save();
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login');
        }
    }

    public function destroy($id)
    {
        if (Auth::check()) {
            $task = Task::find($id);
            $task->delete();
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login');
        }
    }

    public function setweek(Request $request)
    {
        $year = now()->year;
        $month = now()->month;
        $day = now()->day;

        return redirect()->route('week', ['year' => $year, 'month' => $month, 'day' => $day]);
    }

    public function moveweek(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        $day = $request->query('day');
        $offset = $request->query('offset');

        if ($offset == 'back') {
            $offset = -7;
        } elseif ($offset == 'next') {
            $offset = 7;
        } else {
            $offset = 0;
        }

        $startDate = Carbon::createFromDate($year, $month, $day)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();

        $startDate->addDays($offset);
        $endDate->addDays($offset);

        $year = $startDate->year;
        $month = $startDate->month;
        $day = $startDate->day;

        return redirect()->route('week', ['year' => $year, 'month' => $month, 'day' => $day]);
    }

    public function week(Request $request, $year, $month, $day)
    {

        $user_id = Auth::user()->id;
        $startDate = Carbon::createFromDate($year, $month, $day)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();


        $tasks = Task::where('user_id', $user_id)
            ->whereBetween('begin', [$startDate, $endDate])
            ->get();

        $start = $startDate->format('Y/m/d');
        $end = $endDate->format('Y/m/d');

        return view('week', compact('tasks', 'year', 'month', 'day', 'start', 'end'));
    }

    public function setmonth(Request $request)
    {
        $year = now()->year;
        $month = now()->month;

        return redirect()->route('month', ['year' => $year, 'month' => $month]);
    }

    public function movemonth(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        $offset = $request->query('offset');


        if ($offset == 'back') {
            $offset = -1;
        } elseif ($offset == 'next') {
            $offset = 1;
        } else {
            $offset = 0;
        }
        $month = $month + $offset;

        if ($month > 12) {
            $year += floor($month / 12);
            $month = $month % 12;
        } elseif ($month < 1) {
            $year += ceil($month / 12) - 1;
            $month = 12 + ($month % 12);
        }

        return redirect()->route('month', ['year' => $year, 'month' => $month]);
    }

    public function month(Request $request, $year, $month)
    {
        $user_id = Auth::user()->id;
        $monthName = getMonthName($month);

        $tasks = Task::where('user_id', $user_id)
            ->whereYear('begin', $year)
            ->whereMonth('begin', $month)
            ->orderBy('begin', 'asc')
            ->get();

        return view('month', compact('tasks', 'year', 'month', 'monthName'));
    }
}

function getMonthName($month)
{
    $monthNames = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    return $monthNames[$month] ?? 'Invalid Month';
}
