function toggleUserMenu() {
    document.getElementById('userDropdown')
        .classList.toggle('active');
}

document.addEventListener('click', function (e) {

    const menu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');

    if (!menu || !dropdown) return;

    if (!menu.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});