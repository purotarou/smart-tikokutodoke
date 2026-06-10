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

    // セレクトに「選択してください」＋選択肢を入れる
    function fill(sel, items) {
        sel.innerHTML = '<option value="">選択してください</option>';
        items.forEach(function (it) {
            const o = document.createElement('option');
            o.value = it.value;
            o.textContent = it.text;
            sel.appendChild(o);
        });
    }
    // セレクトを「選択してください」だけに戻して、選べないようにする
    function resetSelect(sel) {
        sel.innerHTML = '<option value="">選択してください</option>';
        sel.value = '';
        sel.disabled = true;
    }
    function uniqueNums(arr) {
        return Array.from(new Set(arr)).sort(function (a, b) { return a - b; });
    }
    // 学年・組・番号がそろって1人に決まったら、送信できるようにする
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

    // 学年の選択肢を初期化（最初は組・番号・名前は選べない状態）
    const grades = uniqueNums(data.map(function (s) { return s.grade; }));
    fill(selGrade, grades.map(function (g) { return { value: g, text: g + '年' }; }));

    // 学年を選ぶと、組が選べるようになる
    selGrade.addEventListener('change', function () {
        resetSelect(selClass);
        resetSelect(selNumber);
        resetSelect(selName);
        const g = selGrade.value;
        if (g) {
            const inGrade = data.filter(function (s) { return s.grade === g; });
            const classes = uniqueNums(inGrade.map(function (s) { return s.klass; }));
            fill(selClass, classes.map(function (c) { return { value: c, text: c + '組' }; }));
            selClass.disabled = false;
        }
        updateState();
    });

    // 組を選ぶと、出席番号と名前が選べるようになる
    selClass.addEventListener('change', function () {
        resetSelect(selNumber);
        resetSelect(selName);
        const g = selGrade.value, c = selClass.value;
        if (g && c) {
            const inGC = data.filter(function (s) { return s.grade === g && s.klass === c; });
            const numbers = uniqueNums(inGC.map(function (s) { return s.number; }));
            fill(selNumber, numbers.map(function (n) { return { value: n, text: n + '番' }; }));
            selNumber.disabled = false;
            fill(selName, inGC.map(function (s) { return { value: s.id, text: s.name }; }));
            selName.disabled = false;
        }
        updateState();
    });

    // 出席番号を選ぶと、同じ生徒の名前も自動で合わせる
    selNumber.addEventListener('change', function () {
        const g = selGrade.value, c = selClass.value, n = selNumber.value;
        if (g && c && n) {
            const st = data.find(function (s) {
                return s.grade === g && s.klass === c && s.number === n;
            });
            selName.value = st ? st.id : '';
        } else {
            selName.value = '';
        }
        updateState();
    });

    // 名前を選ぶと、同じ生徒の出席番号も自動で合わせる
    selName.addEventListener('change', function () {
        const id = selName.value;
        if (id) {
            const st = data.find(function (s) { return s.id === id; });
            if (st) { selNumber.value = st.number; }
        } else {
            selNumber.value = '';
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
