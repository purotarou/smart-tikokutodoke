// =============================================================
// 管理画面の絞り込みセレクトを、学年→組→番号→名前の順に連動させます。
//
// 挙動（manual_entry.php と同じ）:
//   ・最初は「組・番号・名前」は選べない（無効）状態。
//   ・学年を選ぶと「組」が選べるようになる。
//   ・組を選ぶと「番号」「名前」が選べるようになる。
//
// 選択肢は生徒名簿（student_info）から作るので、
// まだ遅刻の記録が無い学年・組・番号・名前も、あらかじめ選べます。
//
// ページ側で次の2つを用意してください:
//   window.FILTER_STUDENTS = [{grade, class, number, name}, ...]; // 生徒名簿
//   window.FILTER_CURRENT  = {class:'', number:'', name:''};       // 今の絞り込み
// セレクトのidは filter-grade / filter-class / filter-number / filter-name。
// =============================================================
(function () {
    var selGrade = document.getElementById('filter-grade');
    if (!selGrade) { return; } // 絞り込みのあるページ以外では何もしない

    var selClass  = document.getElementById('filter-class');
    var selNumber = document.getElementById('filter-number');
    var selName   = document.getElementById('filter-name');

    // 名簿データを文字列にそろえて持っておく
    var data = (window.FILTER_STUDENTS || []).map(function (s) {
        return {
            grade:  String(s.grade),
            klass:  String(s.class),
            number: String(s.number),
            name:   String(s.name)
        };
    });
    var current = window.FILTER_CURRENT || {};

    // 重複を除いて並べ替える（numeric=true なら数として小さい順）
    function uniqueSorted(values, numeric) {
        var u = Array.from(new Set(values));
        u.sort(function (a, b) {
            return numeric ? (a - b) : String(a).localeCompare(String(b), 'ja');
        });
        return u;
    }

    // セレクトに「すべて」＋選択肢を入れ直す。selected があればそれを選ぶ。
    function fill(sel, items, selected) {
        if (!sel) { return; }
        sel.innerHTML = '';
        var all = document.createElement('option');
        all.value = '';
        all.textContent = 'すべて';
        sel.appendChild(all);
        items.forEach(function (it) {
            var o = document.createElement('option');
            o.value = it.value;
            o.textContent = it.text;
            if (selected != null && String(selected) === String(it.value)) {
                o.selected = true;
            }
            sel.appendChild(o);
        });
    }

    // 今選ばれている学年・組にあてはまる名簿の行
    function matchedRows() {
        var g = selGrade.value;
        var c = selClass ? selClass.value : '';
        return data.filter(function (s) {
            if (g && s.grade !== g) { return false; }
            if (c && s.klass !== c) { return false; }
            return true;
        });
    }

    // 組: 学年が選ばれていれば選択肢を作って有効化。未選択なら空＆無効。
    function rebuildClass(selected) {
        if (!selClass) { return; }
        if (selGrade.value) {
            var src = data.filter(function (s) { return s.grade === selGrade.value; });
            var classes = uniqueSorted(src.map(function (s) { return s.klass; }), true);
            fill(selClass, classes.map(function (c) { return { value: c, text: c + '組' }; }), selected);
            selClass.disabled = false;
        } else {
            fill(selClass, [], null); // 「すべて」だけ
            selClass.disabled = true;
        }
    }

    // 番号・名前: 組が選ばれていれば選択肢を作って有効化。未選択なら空＆無効。
    function rebuildNumberName(selNum, selNm) {
        var ready = selGrade.value && selClass && selClass.value;
        if (ready) {
            var rows = matchedRows();
            var numbers = uniqueSorted(rows.map(function (s) { return s.number; }), true);
            fill(selNumber, numbers.map(function (n) { return { value: n, text: n + '番' }; }), selNum);

            var seen = {}, names = [];
            rows.forEach(function (s) {
                if (!seen[s.name]) { seen[s.name] = true; names.push(s.name); }
            });
            names.sort(function (a, b) { return a.localeCompare(b, 'ja'); });
            fill(selName, names.map(function (n) { return { value: n, text: n }; }), selNm);

            if (selNumber) { selNumber.disabled = false; }
            if (selName)   { selName.disabled = false; }
        } else {
            fill(selNumber, [], null);
            fill(selName, [], null);
            if (selNumber) { selNumber.disabled = true; }
            if (selName)   { selName.disabled = true; }
        }
    }

    // 学年を変えたら、組を作り直し、番号・名前は無効に戻す
    selGrade.addEventListener('change', function () {
        rebuildClass('');
        rebuildNumberName('', '');
    });
    // 組を変えたら、番号・名前を作り直す
    if (selClass) {
        selClass.addEventListener('change', function () {
            rebuildNumberName('', '');
        });
    }

    // 最初の表示：今の絞り込み状態を復元する
    rebuildClass(current.class);
    rebuildNumberName(current.number, current.name);
})();
