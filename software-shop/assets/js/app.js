const cartCount = document.querySelector("#cart-count");
const cartButtons = document.querySelectorAll(".add-to-cart");

let cartQuantity = 0;

cartButtons.forEach((button) => {
    button.addEventListener("click", () => {
        cartQuantity++;

        cartCount.textContent = cartQuantity;

        const productName = button.dataset.product;

        button.textContent = "Hinzugefügt ✓";

        console.log(`${productName} wurde hinzugefügt.`);
    });
});
