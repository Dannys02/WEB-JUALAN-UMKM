const status = document.getElementById("status_sukses");

if (status) {
    status.style.display = "block";

    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete("status");
        window.history.replaceState({ path: url.href }, "", url.href);
    }

    setTimeout(() => {
        status.style.opacity = "0";
        setTimeout(() => {
            status.style.display = "none";
        }, 500);
    }, 5000);
}
