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

// 楽曲選択後のアーティスト名表示を非表示にする
function updateSongSelectDisplay() {
  // FilamentのSelectコンポーネント（Tom Select）を探す
  document.querySelectorAll('select[name*="[song]"]').forEach(select => {
    // 選択されたオプションを取得
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.text) {
      const text = selectedOption.text;
      // 「 - アーティスト名」の部分を削除
      const titleOnly = text.split(' - ')[0];
      
      // Tom Selectの表示要素を更新
      const tomSelect = select.tomselect || select.tomSelect;
      if (tomSelect) {
        // 選択された値に対応するアイテムを更新
        const selectedValue = select.value;
        if (selectedValue) {
          // 少し遅延してから更新（Livewireの更新を待つ）
          setTimeout(() => {
            const control = tomSelect.control;
            if (control) {
              const singleValue = control.querySelector('.item');
              if (singleValue && singleValue.textContent !== titleOnly) {
                singleValue.textContent = titleOnly;
              }
            }
          }, 100);
        }
      } else {
        // Tom Selectがまだ初期化されていない場合、直接オプションのテキストを更新
        if (selectedOption.text !== titleOnly) {
          selectedOption.text = titleOnly;
        }
      }
    }
  });
}

// DOMContentLoaded時にも実行
document.addEventListener('DOMContentLoaded', () => {
  // 全てのsongセレクトに変更イベントを追加
  const observer = new MutationObserver(() => {
    setTimeout(updateSongSelectDisplay, 100);
  });
  
  // フォーム全体を監視
  const form = document.querySelector('form');
  if (form) {
    observer.observe(form, {
      childList: true,
      subtree: true
    });
  }
  
  // 定期的にチェック（Livewire/Alpineの更新に対応）
  setInterval(updateSongSelectDisplay, 300);
  
  // 初期実行
  setTimeout(updateSongSelectDisplay, 500);
});