// =============================================================
// バーコードリーダー（InLight 1D 270U-B）対応
// このリーダーはUSBキーボードとして動き、読み取った学籍番号を
// 「キー入力」し、最後にEnterを送ります。
// 画面に入力欄は置かず、ページ全体のキー入力を拾って学籍番号を
// 組み立て、Enterを受け取ったら hidden 欄に入れて送信します。
// =============================================================
(function () {
    const studentForm = document.getElementById('student-form');
    if (!studentForm) { return; } // index.html 以外では何もしない

    const studentIdInput = document.getElementById('student-id');
    if (!studentIdInput) { return; }

    let buffer = ''; // 読み取り中の学籍番号を組み立てるための一時保管

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (buffer === '') { return; }
            studentIdInput.value = buffer;
            buffer = '';
            studentForm.submit();
        } else if (e.key.length === 1 && e.key >= '0' && e.key <= '9') {
            // 学籍番号は数字なので、数字キーだけを拾う
            buffer += e.key;
        }
    });
})();

// =============================================================
// 学年・組・名前から選ぶ画面（manual_entry.php）の連動処理
// STUDENTS は manual_entry.php のインライン <script> で定義されます。
// =============================================================
(function () {
    const selGrade  = document.getElementById('sel-grade');
    if (!selGrade) { return; } // このページ以外では何もしない

    const selClass  = document.getElementById('sel-class');
    const selNumber = document.getElementById('sel-number');
    const selName   = document.getElementById('sel-name');
    const hiddenId  = document.getElementById('student-id');
    const submitBtn = document.getElementById('manual-submit');

    const data = STUDENTS.map(function (s) {
        return {
            id:     String(s.student_id),
            grade:  String(s.grade),
            klass:  String(s.class),
            number: String(s.number),
            name:   s.name
        };
    });

    function fill(sel, items) {
        sel.innerHTML = '<option value="">選択してください</option>';
        items.forEach(function (it) {
            const o = document.createElement('option');
            o.value = it.value;
            o.textContent = it.text;
            sel.appendChild(o);
        });
    }
    function resetSelect(sel) {
        sel.innerHTML = '<option value="">選択してください</option>';
        sel.value = '';
        sel.disabled = true;
    }
    function uniqueNums(arr) {
        return Array.from(new Set(arr)).sort(function (a, b) { return a - b; });
    }
    function updateState() {
        const g = selGrade.value, c = selClass.value, n = selNumber.value;
        let match = null;
        if (g && c && n) {
            match = data.find(function (s) {
                return s.grade === g && s.klass === c && s.number === n;
            }) || null;
        }
        if (match) {
            hiddenId.value = match.id;
            submitBtn.disabled = false;
        } else {
            hiddenId.value = '';
            submitBtn.disabled = true;
        }
    }

    // 学年の選択肢を初期化
    const grades = uniqueNums(data.map(function (s) { return s.grade; }));
    fill(selGrade, grades.map(function (g) { return { value: g, text: g + '年' }; }));

    selGrade.addEventListener('change', function () {
        resetSelect(selClass);
        resetSelect(selNumber);
        resetSelect(selName);
        const g = selGrade.value;
        if (!g) { updateState(); return; }

        const inGrade = data.filter(function (s) { return s.grade === g; });
        const classes = uniqueNums(inGrade.map(function (s) { return s.klass; }));
        fill(selClass, classes.map(function (c) { return { value: c, text: c + '組' }; }));
        selClass.disabled = false;

        fill(selName, inGrade.map(function (s) { return { value: s.id, text: s.name }; }));
        selName.disabled = false;

        updateState();
    });

    selClass.addEventListener('change', function () {
        resetSelect(selNumber);
        const g = selGrade.value, c = selClass.value;
        if (!c) {
            const inGrade = data.filter(function (s) { return s.grade === g; });
            fill(selName, inGrade.map(function (s) { return { value: s.id, text: s.name }; }));
            selName.disabled = false;
            updateState();
            return;
        }
        const inGC = data.filter(function (s) { return s.grade === g && s.klass === c; });
        const numbers = uniqueNums(inGC.map(function (s) { return s.number; }));
        fill(selNumber, numbers.map(function (n) { return { value: n, text: n + '番' }; }));
        selNumber.disabled = false;
        fill(selName, inGC.map(function (s) { return { value: s.id, text: s.name }; }));
        selName.disabled = false;
        selName.value = '';
        updateState();
    });

    selNumber.addEventListener('change', function () {
        const g = selGrade.value, c = selClass.value, n = selNumber.value;
        if (g && c && n) {
            const st = data.find(function (s) {
                return s.grade === g && s.klass === c && s.number === n;
            });
            if (st) { selName.value = st.id; }
        }
        updateState();
    });

    selName.addEventListener('change', function () {
        const id = selName.value;
        if (!id) { updateState(); return; }
        const st = data.find(function (s) { return s.id === id; });
        if (st) {
            selClass.value = st.klass;
            const inGC = data.filter(function (s) { return s.grade === st.grade && s.klass === st.klass; });
            const numbers = uniqueNums(inGC.map(function (s) { return s.number; }));
            fill(selNumber, numbers.map(function (n) { return { value: n, text: n + '番' }; }));
            selNumber.disabled = false;
            selNumber.value = st.number;
        }
        updateState();
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
