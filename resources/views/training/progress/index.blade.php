@extends('layouts.master')

@section('content')
<div class="container">
    <h2 class="mb-4">My Training Progress</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Training ID</th>
                <th>Completed Contents</th>
                <th>Total Contents</th>
                <th>Progress %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totalContents as $trainingId => $training)
                @php
                    $completed = $completedContents[$trainingId]->completed ?? 0;
                    $total = $training->total;
                    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                @endphp
                <tr>
                    <td>{{ $trainingId }}</td>
                    <td>{{ $completed }}</td>
                    <td>{{ $total }}</td>
                    <td>{{ $percentage }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
