function toggleSellerFields(sellerId, checked) {
  const fields = document.getElementById("seller_" + sellerId + "_fields");
  if (fields) {
    fields.style.display = checked ? "block" : "none";
  }
}
