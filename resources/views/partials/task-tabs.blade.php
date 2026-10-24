<nav class="tabs" aria-label="Chế độ xem">
    <a href="{{ route('tasks') }}" @class(['active' => request()->routeIs('tasks')])>Tổng quan</a>
    <a href="{{ route('tasks.daily') }}" @class(['active' => request()->routeIs('tasks.daily')])>Task mỗi ngày</a>
</nav>
