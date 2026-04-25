// cliente.js - Produtos

document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".card-produto");

  cards.forEach((card) => {
    card.addEventListener("click", function () {
      const titulo = card.querySelector(".titulo").innerText;
      const preco = card.querySelector(".preco").innerText;
      const imagem = card.querySelector("img").src;

      const modal = document.createElement("div");
      modal.classList.add("modal-produto");
      modal.innerHTML = `
        <div class="modal-content">
          <span class="fechar-modal">&times;</span>
          <img src="${imagem}" style="width: 100%; max-height: 300px; object-fit: cover;">
          <h2>${titulo}</h2>
          <p>${preco}</p>
        </div>
      `;
      document.body.appendChild(modal);

      modal.querySelector(".fechar-modal").addEventListener("click", () => {
        modal.remove();
      });
    });
  });
});
// === Funções para o carrinho ===
document.addEventListener("DOMContentLoaded", function () {
  const btnsMais = document.querySelectorAll(".btn-mais");
  const btnsMenos = document.querySelectorAll(".btn-menos");

  btnsMais.forEach(btn => {
    btn.addEventListener("click", () => {
      const input = btn.closest("td").querySelector("input");
      input.value = parseInt(input.value) + 1;
      atualizarSubtotal(input);
    });
  });

  btnsMenos.forEach(btn => {
    btn.addEventListener("click", () => {
      const input = btn.closest("td").querySelector("input");
      if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        atualizarSubtotal(input);
      }
    });
  });

  function atualizarSubtotal(input) {
    const preco = parseFloat(input.dataset.preco);
    const quantidade = parseInt(input.value);
    const subtotal = (preco * quantidade).toFixed(2);
    input.closest("tr").querySelector(".subtotal").textContent = subtotal + " MZN";
    atualizarTotalGeral();
  }

  function atualizarTotalGeral() {
    let total = 0;
    document.querySelectorAll(".subtotal").forEach(el => {
      total += parseFloat(el.textContent);
    });
    const totalGeral = document.querySelector(".total-geral span");
    if (totalGeral) {
      totalGeral.textContent = total.toFixed(2) + " MZN";
    }
  }
});
