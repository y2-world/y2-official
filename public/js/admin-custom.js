function insertNumberLabels(prefix, labelClass) {
    const inputs = document.querySelectorAll(`input[name^="${prefix}["][name$="[song]"]`);

    inputs.forEach((input, index) => {
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;

        const existing = inputGroup.previousElementSibling;
        if (existing && existing.classList.contains(labelClass)) {
            existing.remove();
        }

        const label = document.createElement('div');
        label.textContent = `${index + 1}.`;
        label.className = labelClass;
        label.style.marginBottom = '4px';
        label.style.fontWeight = 'bold';
        label.style.fontSize = '13px';
        label.style.color = '#666';

        inputGroup.parentNode.insertBefore(label, inputGroup);
    });
}

function updateAllNumberLabels() {
    insertNumberLabels('setlist', 'setlist-number-label');
    insertNumberLabels('encore', 'encore-number-label');
    insertNumberLabels('fes_setlist', 'fes-setlist-number-label');
    insertNumberLabels('fes_encore', 'fes-encore-number-label');
}

// 初回実行とクリック追加に対応
function initNumbering() {
    updateAllNumberLabels();

    ['.has-many-setlist', '.has-many-encore', '.has-many-fes_setlist', '.has-many-fes_encore'].forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
            container.addEventListener('click', () => {
                setTimeout(updateAllNumberLabels, 100);
            });
        }
    });
}

// 初回ロード
document.addEventListener('DOMContentLoaded', initNumbering);

// pjax遷移後にも対応
$(document).on('pjax:end', initNumbering);