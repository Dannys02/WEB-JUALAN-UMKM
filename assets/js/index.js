let hargaAsli = 0;

function openModal(id, nama, harga) {
    document.getElementById("orderModal").classList.remove("hidden");
    document.getElementById("produk_id").value = id;
    document.getElementById("modalTitle").innerText = "Pesan: " + nama;

    hargaAsli = harga;
    document.getElementById("harga_modal").value = harga;
    document.getElementById("stok").value = 1;
}

function hitungTotal() {
    let jumlah = document.getElementById("stok").value;
    document.getElementById("harga_modal").value = hargaAsli * jumlah;
}

function closeModal() {
    document.getElementById("orderModal").classList.add("hidden");
}
