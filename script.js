
window.submitForm = function () {
    const studentForm = document.getElementById('student-form');
    const studentIdInput = document.getElementById('student-id');
    const studentId = studentIdInput.value;

    if (!studentId) {
        return;
    }

    studentForm.submit();
};

//提出完了画面に遷移
const submitBtn = document.querySelector('.submit-btn');

if (submitBtn && !submitBtn.form) {
    submitBtn.addEventListener('click', function () {
        window.location.href = 'end.html';
    });
}

//「その他」を選択したときにテキスト入力欄にフォーカスする
const otherReasonCheck = document.querySelector('.other-reason-check');
const otherReasonText = document.querySelector('.other-reason-text');

if (otherReasonCheck && otherReasonText) {
    otherReasonCheck.addEventListener('change', function () {
        if (this.checked) {
            otherReasonText.focus();
        }
    });
}
