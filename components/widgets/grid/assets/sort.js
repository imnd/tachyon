var 
    sortOrder = "DESC",
    sortCols,
    sortField,
    parser = new DOMParser()
;
var sort = function(url, field, tblId) {
    sortOrder = sortField!==field ? "ASC" : sortOrder=="DESC" ? "ASC" : "DESC";
    sortField = field;
    ajax.get(
        url,
        {
            field : sortField,
            order : sortOrder,
        },
        function(resp) {
            var xmlDoc = parser.parseFromString(resp, "text/html");
            var newTable = dom.findByClass("data-grid", xmlDoc);
            var newTableId = newTable.id;
            var oldTable = dom.findById(tblId);
            oldTable.innerHTML = newTable.innerHTML;
            oldTable.id = newTableId;
            bindSortHandlers(url, sortCols, newTableId);
            dom.findById(field).className = sortOrder + " sortable-column";
        },
        "html"
    );
};
// прикручиваем обработчик к ячейкам таблицы
var bindSortHandler = function(url, field, tblId) {
    dom.findById(field).addEventListener("click", function() {
        sort(url, field, tblId);
    });
};
// прикручиваем обработчики к ячейкам таблицы
var bindSortHandlers = function(url, columns, tblId) {
    sortCols = columns;
    for (var key=0; key<columns.length; key++)
        bindSortHandler(url, columns[key], tblId)
};
