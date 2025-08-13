function insertNumberLabels(prefix, labelClass) {
  const inputs = document.querySelectorAll(
    `input[name^="${prefix}["][name$="[song]"]`
  );

  inputs.forEach((input, index) => {
    const inputGroup = input.closest(".input-group");
    if (!inputGroup) return;

    // 既存ラベルがあれば削除
    const existing = inputGroup.querySelector(`.${labelClass}`);
    if (existing) existing.remove();

    // 新しく番号ラベル作成
    const label = document.createElement("div");
    label.textContent = `${index + 1}.`;
    label.className = labelClass;

    // スタイル設定（JSでCSSも設定）
    label.style.fontWeight = "bold";
    label.style.color = "#666";
    label.style.fontSize = "13px";
    label.style.marginRight = "8px";
    label.style.whiteSpace = "nowrap";

    // inputGroupの先頭に挿入
    inputGroup.prepend(label);
  });
}

function insertSelectNumberLabels(prefix, labelClass) {
  const selects = document.querySelectorAll(
    `select[name^="${prefix}["][name$="[song]"]`
  );

  selects.forEach((select, index) => {
    const formGroup = select.closest(".form-group");
    if (!formGroup) return;

    // 既存ラベルがあれば削除
    const existing = formGroup.querySelector(`.${labelClass}`);
    if (existing) existing.remove();

    // 新しく番号ラベル作成
    const label = document.createElement("div");
    label.textContent = `${index + 1}.`;
    label.className = labelClass;

    // スタイル設定
    label.style.fontWeight = "bold";
    label.style.color = "#666";
    label.style.fontSize = "10px";
    label.style.marginRight = "8px";
    label.style.whiteSpace = "nowrap";

    // 先頭に挿入
    formGroup.prepend(label);
  });
}

function updateAllNumberLabels() {
  insertNumberLabels("setlist", "setlist-number-label");
  insertNumberLabels("fes_setlist", "fes-setlist-number-label");
  insertNumberLabels("encore", "encore-number-label");
  insertNumberLabels("fes_encore", "fes-encore-number-label");

  insertSelectNumberLabels("setlist", "setlist-number-label");
  insertSelectNumberLabels("encore", "encore-number-label");
}

function initNumbering() {
  updateAllNumberLabels();

  [
    ".has-many-setlist",
    ".has-many-encore",
    ".has-many-fes_setlist",
    ".has-many-fes_encore",
  ].forEach((selector) => {
    const container = document.querySelector(selector);
    if (container) {
      container.addEventListener("click", () => {
        setTimeout(updateAllNumberLabels, 100);
      });
    }
  });
}

document.addEventListener("DOMContentLoaded", initNumbering);
$(document).on("pjax:end", initNumbering);

document.addEventListener("DOMContentLoaded", () => {
  // 新規ボタンが押されたときに番号を即更新
  document.querySelectorAll('.add.btn').forEach(button => {
    button.addEventListener('click', () => {
      setTimeout(updateAllNumberLabels, 10); // 追加DOMが反映されるタイミングに合わせて遅延実行
    });
  });
});