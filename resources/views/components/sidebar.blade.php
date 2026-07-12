<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="#">Simanda</a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="#">SMD</a>
        </div>
        <ul class="sidebar-menu">
            <li class="{{ Request::is('dashboard') ? 'active' : '' }}">
                <a class="nav-link"
                    href="{{ url('/dashboard') }}"><i class="fas fa-fire"></i> <span>Dashboard</span></a>
            </li>
            @if (Auth::user()->hasRole('Super Admin'))
                <li class="menu-header">User Management</li>
                <li class="{{ Request::is('jabatan') ? 'active' : '' }}">
                    <a class="nav-link"
                        href="{{ url('/jabatan') }}"><i class="fas fa-briefcase"></i> <span>Jabatan</span></a>
                </li>
                <li class="{{ Request::is('users') ? 'active' : '' }}">
                    <a class="nav-link"
                        href="{{ url('/users') }}"><i class="fas fa-users"></i> <span>Users</span></a>
                </li>
                <li class="{{ Request::is('roles') ? 'active' : '' }}">
                    <a class="nav-link"
                        href="{{ url('/roles') }}"><i class="fas fa-table"></i> <span>Roles</span></a>
                </li>
                <li class="{{ Request::is('export-setting') ? 'active' : '' }}">
                    <a class="nav-link"
                        href="{{ url('/export-setting') }}"><i class="fas fa-cog"></i> <span>Export Setting</span></a>
                </li>
            @endif
        </ul>

    </aside>
</div>
