import ajax from 'imnd-ajax';
import dom from 'imnd-dom';

let
  sortOrder = 'DESC',
  sortCols,
  sortField
;
const
  parser = new DOMParser(),
  sort = (field, tblId, url) => {
    sortOrder = sortField !== field ? 'ASC' : sortOrder === 'DESC' ? 'DESC' : 'ASC';
    sortField = field;
    ajax
      .get(
        url,
        {
          'order-by': sortField,
          order: sortOrder,
        },
        'html'
      )
      .then(
        result => {
          const
            xmlDoc = parser.parseFromString(result, 'text/html'),
            newTable = dom(xmlDoc).findByClass('data-grid'),
            newTableId = newTable.id();

          dom(`#${tblId}`)
            .html(newTable.html())
            .id(newTableId);

          bindSortHandlers(sortCols, newTableId, url);
          dom(`#${field}`).class(`${sortOrder} sortable-column`);
        }
      );
  };

// прикручиваем обработчик к ячейкам таблицы
const bindSortHandler = (field, tblId, url) => {
  dom(`#${field}`)
    .attr('style', 'cursor: pointer')
    .click(() => sort(field, tblId, url));
};

// прикручиваем обработчики к ячейкам таблицы
const bindSortHandlers = (fields, tblId, url) => {
  sortCols = fields;
  dom(() => {
    for (let key = 0; key < fields.length; key++) {
      bindSortHandler(fields[key], tblId, url || "")
    }
  });
};

export default bindSortHandlers;
