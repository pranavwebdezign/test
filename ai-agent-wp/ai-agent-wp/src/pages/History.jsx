import { useState } from 'react';
import {
  Box, Typography, Button, Chip, TextField,
  MenuItem, Select, FormControl, InputLabel,
  Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Paper, TablePagination,
  Stack, InputAdornment,
} from '@mui/material';
import {
  Refresh, Search, CheckCircle, Cancel,
} from '@mui/icons-material';
import { CircularProgress } from '@mui/material';
import { formatDistanceToNow, format } from 'date-fns';
import AppLayout from '../components/layout/AppLayout';
import { mockCheckHistory } from '../data/mockData';

const statusConfig = {
  success: { color: 'success', label: 'Success', Icon: CheckCircle },
  failed: { color: 'error', label: 'Failed', Icon: Cancel },
  running: { color: 'info', label: 'Running', Icon: CircularProgress },
  partial: { color: 'warning', label: 'Partial', Icon: CheckCircle },
};

export default function History() {
  const [page, setPage] = useState(0);
  const [rowsPerPage] = useState(10);
  const [statusFilter, setStatusFilter] = useState('all');

  const filtered = statusFilter === 'all'
    ? mockCheckHistory
    : mockCheckHistory.filter((r) => r.status === statusFilter);

  const paginated = filtered.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Check History</Typography>
            <Typography variant="body2" color="text.secondary">View past automated health checks</Typography>
          </Box>
          <Button variant="contained" startIcon={<Refresh />}>Run All Checks</Button>
        </Box>

        {/* Table */}
        <Paper variant="outlined">
          {/* Filters */}
          <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <TextField
              placeholder="Search by site..."
              size="small"
              InputProps={{ startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> }}
              sx={{ width: 240 }}
            />
            <FormControl size="small" sx={{ minWidth: 160 }}>
              <InputLabel>Status</InputLabel>
              <Select value={statusFilter} label="Status" onChange={(e) => { setStatusFilter(e.target.value); setPage(0); }}>
                <MenuItem value="all">All Status</MenuItem>
                <MenuItem value="success">Success</MenuItem>
                <MenuItem value="failed">Failed</MenuItem>
                <MenuItem value="partial">Partial</MenuItem>
              </Select>
            </FormControl>
          </Box>

          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ background: '#fafafa' }}>
                  <TableCell><strong>Site</strong></TableCell>
                  <TableCell><strong>Started</strong></TableCell>
                  <TableCell><strong>Duration</strong></TableCell>
                  <TableCell><strong>Status</strong></TableCell>
                  <TableCell><strong>Findings</strong></TableCell>
                  <TableCell />
                </TableRow>
              </TableHead>
              <TableBody>
                {paginated.map((row) => {
                  const cfg = statusConfig[row.status] || statusConfig.partial;
                  const duration = row.completedAt
                    ? ((row.completedAt - row.startedAt) / 1000).toFixed(1) + 's'
                    : '—';
                  return (
                    <TableRow key={row.id} hover>
                      <TableCell>
                        <Typography variant="body2" fontWeight={600}>{row.siteName}</Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{format(row.startedAt, 'MMM d, yyyy HH:mm')}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          {formatDistanceToNow(row.startedAt, { addSuffix: true })}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{duration}</Typography>
                      </TableCell>
                      <TableCell>
                        <Chip label={cfg.label} color={cfg.color} size="small" />
                      </TableCell>
                      <TableCell>
                        {row.findingsCount > 0 ? (
                          <Chip label={`${row.findingsCount} finding${row.findingsCount !== 1 ? 's' : ''}`} color="warning" size="small" />
                        ) : (
                          <Chip label="No issues" color="success" size="small" variant="outlined" />
                        )}
                      </TableCell>
                      <TableCell>
                        <Button size="small">View Details</Button>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={filtered.length}
            page={page}
            onPageChange={(_, p) => setPage(p)}
            rowsPerPage={rowsPerPage}
            rowsPerPageOptions={[10]}
          />
        </Paper>
      </Box>
    </AppLayout>
  );
}
