<div class="row">
    <div class="col-md-4">
        <div class="card border-warning shadow-sm">
            <div class="card-body">
                <h5 class="card-title">🟡 Pending Trainings</h5>
                <p class="card-text display-6 fw-bold text-warning">{{ $trainingCounts['pending'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <h5 class="card-title">🔵 In Progress</h5>
                <p class="card-text display-6 fw-bold text-info">{{ $trainingCounts['in_progress'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success shadow-sm">
            <div class="card-body">
                <h5 class="card-title">🟢 Completed</h5>
                <p class="card-text display-6 fw-bold text-success">{{ $trainingCounts['completed'] }}</p>
            </div>
        </div>
    </div>
</div>
