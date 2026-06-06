
//学籍番号の取得仮置き
window.submitForm = function () {
    const studentForm = document.getElementById('student-form');
    const studentIdInput = document.getElementById('student-id');
    const studentId = studentIdInput.value;

    if (!studentId) {
        return;
    }

    studentForm.submit();
};

// =============================================================
// バーコードリーダー（InLight 1D 270U-B）対応
// このリーダーはUSBキーボードとして動き、読み取った学籍番号を
// フォーカス中の入力欄へ「キー入力」し、最後にEnterを送ります。
// そのため学生ID欄に自動でフォーカスを当てておき、読み取り値を
// そのまま学生IDとして受け取って送信します。
// ※読み取った値は開発確認用に画面へ一時表示します（後で削除予定）。
// =============================================================
(function () {
    const studentIdInput = document.getElementById('student-id');
    if (!studentIdInput) {
        return;
    }

    const scanDebugValue = document.getElementById('scan-debug-value');

    // 読み取った値をそのまま受け取れるよう、常に入力欄へフォーカスを当てる
    function focusInput() {
        studentIdInput.focus();
    }
    focusInput();
    // 画面のどこかをクリックしても、読み取り先が入力欄に戻るようにする
    document.addEventListener('click', focusInput);

    // 入力されるたびに、開発確認用の表示を更新する
    studentIdInput.addEventListener('input', function () {
        if (scanDebugValue) {
            scanDebugValue.textContent = studentIdInput.value !== '' ? studentIdInput.value : '（まだ読み取っていません）';
        }
    });

    // バーコードリーダーは読み取り後にEnterを送るので、それで送信する
    studentIdInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            window.submitForm();
        }
    });
})();

//「その他」を選択したときにテキスト入力欄にフォーカスする
const otherReasonCheck = document.querySelector('.other-reason-check');
const otherReasonText = document.querySelector('.other-reason-text');

if (otherReasonCheck && otherReasonText) {
    otherReasonCheck.addEventListener('change', function () {
        if (this.checked) {
            otherReasonText.focus();
        }
    });
    //テキスト入力欄にフォーカスしたときに「その他」を選択する
    otherReasonText.addEventListener('focus', function () {
    otherReasonCheck.checked = true;
    });
}
