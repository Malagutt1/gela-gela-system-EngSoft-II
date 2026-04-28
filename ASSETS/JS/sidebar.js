function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    if (!sidebar) return;

    sidebar.classList.toggle("hidden");

    // Salva o estado: se contém 'hidden', salva 'true', senão 'false'
    const isHidden = sidebar.classList.contains("hidden");
    localStorage.setItem("sidebarHidden", isHidden);
}

// Executa ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const isHidden = localStorage.getItem("sidebarHidden");

    // Se no localStorage estiver 'true', adiciona a classe hidden
    if (isHidden === "true") {
        sidebar.classList.add("hidden");
    }
});