// Подтверждение удаления заказа
document.addEventListener("DOMContentLoaded", () => {
    const deleteLinks = document.querySelectorAll("a[href*='delete_id']");
    deleteLinks.forEach(link => {
        link.addEventListener("click", event => {
            if (!confirm("Вы уверены, что хотите удалить этот заказ?")) {
                event.preventDefault();
            }
        });
    });
});
