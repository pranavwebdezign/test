import { useState, useMemo } from 'react';
import {
  Box, Table, TableBody, TableCell, TableContainer, TableHead,
  TableRow, TablePagination, TableSortLabel, TextField, Paper,
  InputAdornment, Typography, Tooltip, IconButton, Stack, Divider, useMediaQuery, useTheme,
} from '@mui/material';
import { Search, FileDownloadOutlined, InboxOutlined } from '@mui/icons-material';

// ── CSV export helper ─────────────────────────────────────────────
function exportCSV(columns, rows) {
  const headers = columns.filter((c) => c.key !== 'actions').map((c) => c.label);
  const keys = columns.filter((c) => c.key !== 'actions').map((c) => c.key);
  const csvRows = [
    headers.join(','),
    ...rows.map((row) =>
      keys.map((k) => {
        const v = row[k] ?? '';
        return `"${String(v).replace(/"/g, '""')}"`;
      }).join(',')
    ),
  ];
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'export.csv'; a.click();
  URL.revokeObjectURL(url);
}

export default function DataTable({
  columns,
  rows,
  searchKeys,
  defaultSort,
  rowsPerPage: rppProp = 8,
  emptyMessage = 'No records found.',
  sx,
}) {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(0);
  const [rpp] = useState(rppProp);
  const [sort, setSort] = useState(defaultSort ?? { key: columns[0]?.key, dir: 'asc' });
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    return rows.filter((row) =>
      !q || (searchKeys ?? columns.map((c) => c.key)).some((k) =>
        String(row[k] ?? '').toLowerCase().includes(q)
      )
    );
  }, [rows, search, searchKeys, columns]);

  const sorted = useMemo(() => {
    if (!sort.key) return filtered;
    return [...filtered].sort((a, b) => {
      const av = a[sort.key] ?? '';
      const bv = b[sort.key] ?? '';
      return sort.dir === 'asc'
        ? String(av).localeCompare(String(bv), undefined, { numeric: true })
        : String(bv).localeCompare(String(av), undefined, { numeric: true });
    });
  }, [filtered, sort]);

  const paginated = sorted.slice(page * rpp, page * rpp + rpp);

  const handleSort = (key) => {
    setSort((prev) => ({ key, dir: prev.key === key && prev.dir === 'asc' ? 'desc' : 'asc' }));
    setPage(0);
  };

  return (
    <Box sx={sx}>
      {/* ── Toolbar ────────────────────────────────── */}
      {searchKeys !== false && (
        <Box sx={{
          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
          gap: 2, mb: 2, flexWrap: 'wrap',
        }}>
          {/* Search field */}
          <TextField
            size="small"
            placeholder="Search…"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(0); }}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Search fontSize="small" sx={{ color: 'text.secondary' }} />
                </InputAdornment>
              ),
            }}
            sx={{
              width: { xs: '100%', sm: 280 },
              '& .MuiOutlinedInput-root': {
                borderRadius: 2.5,
                bgcolor: '#F7F5FF',
                '& fieldset': { borderColor: '#DDD4F8' },
                '&:hover fieldset': { borderColor: 'primary.main' },
              },
            }}
          />

          {/* Export CSV button */}
          <Tooltip title="Export CSV">
            <IconButton
              size="small"
              onClick={() => exportCSV(columns, sorted)}
              sx={{
                border: '1px solid #DDD4F8', borderRadius: 2,
                px: 1.5, py: 0.8,
                color: 'text.secondary',
                bgcolor: '#F7F5FF',
                gap: 0.5,
                '&:hover': { bgcolor: 'rgba(142,67,240,0.08)', color: 'primary.main', borderColor: 'primary.main' },
                transition: 'all 0.15s',
              }}
            >
              <FileDownloadOutlined sx={{ fontSize: 18 }} />
              <Typography variant="caption" fontWeight={600} sx={{ display: { xs: 'none', sm: 'block' } }}>
                Export
              </Typography>
            </IconButton>
          </Tooltip>
        </Box>
      )}

      {/* ── Mobile card list (xs only) ─────────────────── */}
      {isMobile ? (
        <Box>
          {paginated.length === 0 ? (
            <Box sx={{ py: 7, textAlign: 'center' }}>
              <InboxOutlined sx={{ fontSize: 52, color: '#DDD4F8', mb: 1.5 }} />
              <Typography variant="body2" color="text.secondary" fontWeight={500}>
                {search ? 'No results match your search' : emptyMessage}
              </Typography>
            </Box>
          ) : (
            paginated.map((row, i) => {
              const displayCols = columns.filter((c) => c.key !== 'actions');
              const actionCol = columns.find((c) => c.key === 'actions');
              return (
                <Paper key={row.id ?? i} variant="outlined" sx={{
                  borderRadius: 2.5, border: '1px solid #DDD4F8',
                  borderLeft: '3px solid #8E43F0',
                  mb: 1.5, p: 2, bgcolor: '#fff',
                  transition: 'box-shadow 0.15s',
                  '&:hover': { boxShadow: '0 3px 12px rgba(142,67,240,0.12)' },
                }}>
                  {displayCols.map((col, ci) => (
                    <Box key={col.key} sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: ci < displayCols.length - 1 ? 1 : 0 }}>
                      <Typography variant="caption" color="text.secondary" fontWeight={700} sx={{ fontSize: '0.7rem', textTransform: 'uppercase', letterSpacing: '0.05em', flexShrink: 0, mr: 2, pt: 0.1 }}>
                        {col.label}
                      </Typography>
                      <Box sx={{ textAlign: 'right' }}>
                        {col.render ? col.render(row[col.key], row) : (
                          <Typography variant="body2" fontWeight={500}>{row[col.key]}</Typography>
                        )}
                      </Box>
                    </Box>
                  ))}
                  {actionCol && (
                    <Box sx={{ mt: 1.5, pt: 1.5, borderTop: '1px solid #EDE8FC', display: 'flex', justifyContent: 'flex-end' }}>
                      {actionCol.render ? actionCol.render(row[actionCol.key], row) : null}
                    </Box>
                  )}
                </Paper>
              );
            })
          )}
          {/* Mobile pagination */}
          {filtered.length > rpp && (
            <TablePagination
              component="div" count={filtered.length} page={page}
              rowsPerPage={rpp} rowsPerPageOptions={[rpp]}
              onPageChange={(_, p) => setPage(p)}
              sx={{ borderTop: '1px solid #DDD4F8', bgcolor: '#F7F5FF', mt: 1, borderRadius: 2 }}
            />
          )}
        </Box>
      ) : (
        /* ── Desktop table (sm+) ────────────────────────── */
        <Paper
          variant="outlined"
          sx={{
            borderRadius: 3, overflow: 'hidden',
            border: '1px solid #DDD4F8',
            boxShadow: '0 2px 8px rgba(142,67,240,0.05)',
          }}
        >
          <TableContainer sx={{ maxHeight: 520 }}>
            <Table size="small" stickyHeader>

              {/* ── Header ── */}
              <TableHead>
                <TableRow>
                  {columns.map((col) => (
                    <TableCell
                      key={col.key}
                      align={col.align ?? 'left'}
                      width={col.width}
                      sx={{
                        py: 1.5,
                        bgcolor: '#F7F5FF',
                        borderBottom: '2px solid #DDD4F8',
                      }}
                    >
                      {col.sortable !== false ? (
                        <TableSortLabel
                          active={sort.key === col.key}
                          direction={sort.key === col.key ? sort.dir : 'asc'}
                          onClick={() => handleSort(col.key)}
                          sx={{
                            fontWeight: 700, color: '#5C4A8A !important',
                            fontSize: '0.72rem', textTransform: 'uppercase',
                            letterSpacing: '0.07em',
                            '& .MuiTableSortLabel-icon': { color: '#8E43F0 !important' },
                          }}
                        >
                          {col.label}
                        </TableSortLabel>
                      ) : (
                        <Typography
                          sx={{
                            fontWeight: 700, color: '#5C4A8A',
                            fontSize: '0.72rem', textTransform: 'uppercase',
                            letterSpacing: '0.07em',
                          }}
                        >
                          {col.label}
                        </Typography>
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              </TableHead>

              {/* ── Body ── */}
              <TableBody>
                {paginated.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={columns.length} sx={{ border: 0 }}>
                      {/* Empty state */}
                      <Box sx={{ py: 7, textAlign: 'center' }}>
                        <InboxOutlined sx={{ fontSize: 52, color: '#DDD4F8', mb: 1.5 }} />
                        <Typography variant="body2" color="text.secondary" fontWeight={500} sx={{ mb: 1 }}>
                          {search ? 'No results match your search' : emptyMessage}
                        </Typography>
                        {search && (
                          <Typography
                            variant="caption"
                            color="primary.main"
                            fontWeight={600}
                            sx={{ cursor: 'pointer', '&:hover': { textDecoration: 'underline' } }}
                            onClick={() => { setSearch(''); setPage(0); }}
                          >
                            Clear search
                          </Typography>
                        )}
                      </Box>
                    </TableCell>
                  </TableRow>
                ) : (
                  paginated.map((row, i) => (
                    <TableRow
                      key={row.id ?? i}
                      hover
                      sx={{
                        '&:last-child td': { borderBottom: 0 },
                        '&:hover': { bgcolor: 'rgba(142,67,240,0.03)' },
                        '&:hover td': { color: 'text.primary' },
                        transition: 'background-color 0.12s',
                        cursor: 'default',
                      }}
                    >
                      {columns.map((col) => (
                        <TableCell key={col.key} align={col.align ?? 'left'} sx={{ py: 1.5 }}>
                          {col.render ? col.render(row[col.key], row) : row[col.key]}
                        </TableCell>
                      ))}
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </TableContainer>

          {/* ── Pagination ── */}
          <TablePagination
            component="div"
            count={filtered.length}
            page={page}
            rowsPerPage={rpp}
            rowsPerPageOptions={[rpp]}
            onPageChange={(_, p) => setPage(p)}
            sx={{ borderTop: '1px solid #DDD4F8', bgcolor: '#F7F5FF' }}
          />
        </Paper>
      )}
    </Box>
  );
}
