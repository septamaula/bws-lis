/**
 * Bank Woori Saudara - High Performance Export Utility (Client-side Blob)
 * Generates instant CSV & Excel downloads with zero network latency.
 */

// Helper to resolve nested property paths (e.g. 'user.name' or 'training_class.program.title')
export function getNestedValue(obj, path) {
  if (!obj || !path) return '';
  return path.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : ''), obj);
}

/**
 * Export array of data to CSV with UTF-8 BOM (properly displays Indonesian & currency characters in MS Excel)
 * @param {Array} data
 * @param {Array} columns - Array of { key, label, format }
 * @param {string} filename
 */
export function exportToCsv(data, columns, filename = 'bws_export') {
  if (!data || !data.length) return false;

  const resolvedCols = columns.filter(c => c.key && !c.ignoreExport);
  const headers = resolvedCols.map(c => `"${(c.label || c.key).replace(/"/g, '""')}"`).join(',');

  const rows = data.map(row => {
    return resolvedCols.map(c => {
      let val = c.format ? c.format(getNestedValue(row, c.key), row) : getNestedValue(row, c.key);
      if (val === null || val === undefined) val = '';
      const strVal = String(val).replace(/"/g, '""');
      return `"${strVal}"`;
    }).join(',');
  });

  const csvContent = '\uFEFF' + [headers, ...rows].join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  triggerDownload(blob, `${filename}.csv`);
  return true;
}

/**
 * Export array of data to native Excel XML Spreadsheet format (.xls/.xlsx compatible)
 * @param {Array} data
 * @param {Array} columns
 * @param {string} filename
 */
export function exportToExcel(data, columns, filename = 'bws_export') {
  if (!data || !data.length) return false;

  const resolvedCols = columns.filter(c => c.key && !c.ignoreExport);

  let xml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="HeaderStyle">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F5C38"/>
   </Borders>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#157F4F" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="DataRow">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Sheet1">
  <Table>
`;

  // Define Column Widths
  resolvedCols.forEach(() => {
    xml += `   <Column ss:AutoFitWidth="1" ss:Width="140"/>\n`;
  });

  // Header Row
  xml += `   <Row ss:Height="26">\n`;
  resolvedCols.forEach(c => {
    const label = (c.label || c.key).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    xml += `    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">${label}</Data></Cell>\n`;
  });
  xml += `   </Row>\n`;

  // Data Rows
  data.forEach(row => {
    xml += `   <Row ss:Height="20" ss:StyleID="DataRow">\n`;
    resolvedCols.forEach(c => {
      let val = c.format ? c.format(getNestedValue(row, c.key), row) : getNestedValue(row, c.key);
      if (val === null || val === undefined) val = '';
      
      const isNum = typeof val === 'number';
      const cleanVal = String(val).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      
      if (isNum) {
        xml += `    <Cell><Data ss:Type="Number">${val}</Data></Cell>\n`;
      } else {
        xml += `    <Cell><Data ss:Type="String">${cleanVal}</Data></Cell>\n`;
      }
    });
    xml += `   </Row>\n`;
  });

  xml += `  </Table>
 </Worksheet>
</Workbook>`;

  const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  triggerDownload(blob, `${filename}.xls`);
  return true;
}

function triggerDownload(blob, fullFilename) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = fullFilename;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 100);
}

