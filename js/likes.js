// いいね機能のJavaScript処理

// いいねボタンがクリックされた時の処理
function toggleLike(type, id, button) {
  // ボタンを一時的に無効化
  button.disabled = true;

  // Ajax リクエストを送信
  fetch("like_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `type=${type}&id=${id}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // いいね数を更新
        const countElement = button.querySelector(".like-count");
        countElement.textContent = data.like_count;

        // ボタンの見た目を更新
        const iconElement = button.querySelector("i");
        if (data.is_liked) {
          button.classList.remove("btn-outline-danger");
          button.classList.add("btn-danger");
          iconElement.classList.remove("bi-heart");
          iconElement.classList.add("bi-heart-fill");
        } else {
          button.classList.remove("btn-danger");
          button.classList.add("btn-outline-danger");
          iconElement.classList.remove("bi-heart-fill");
          iconElement.classList.add("bi-heart");
        }
      } else {
        alert("エラーが発生しました: " + (data.message || "不明なエラー"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("通信エラーが発生しました");
    })
    .finally(() => {
      // ボタンを再度有効化
      button.disabled = false;
    });
}

// DOMが読み込まれた後に実行
document.addEventListener("DOMContentLoaded", function () {
  // 全てのいいねボタンにイベントリスナーを追加
  document.querySelectorAll(".like-button").forEach((button) => {
    button.addEventListener("click", function () {
      const type = this.dataset.type;
      const id = this.dataset.id;
      toggleLike(type, id, this);
    });
  });
});
