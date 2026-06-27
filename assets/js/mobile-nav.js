function toggleSidebar() {
    document.querySelector('.sidebar')?.classList.toggle('open');
    document.querySelector('.sidebar-overlay')?.classList.toggle('show');
    document.body.classList.toggle('nav-open');
}

function closeSidebar() {
    document.querySelector('.sidebar')?.classList.remove('open');
    document.querySelector('.sidebar-overlay')?.classList.remove('show');
    document.body.classList.remove('nav-open');
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) closeSidebar();
    });
});
