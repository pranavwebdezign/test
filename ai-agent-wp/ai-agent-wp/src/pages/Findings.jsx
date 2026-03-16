import {
  Box, Typography, Button, Chip,
  Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Paper, TablePagination, IconButton, Tooltip,
} from '@mui/material';
import { Add, OpenInNew } from '@mui/icons-material';
import { formatDistanceToNow } from 'date-fns';
import AppLayout from '../components/layout/AppLayout';
import { mockFindings } from '../data/mockData';

const severityColors = {
  P0: 'error',
  P1: 'warning',
  P2: 'info',
  P3: 'default',
};

const checkTypeLabels = {
  php_version: 'PHP Version',
  wp_core: 'WP Core',
  plugin_updates: 'Plugins',
  theme_updates: 'Theme',
  security: 'Security',
  uptime: 'Uptime',
};

import { useState } from 'react';

export default function Findings() {
  const [page, setPage] = useState(0);
  const [rowsPerPage] = useState(10);

  const paginated = mockFindings.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Findings</Typography>
            <Typography variant="body2" color="text.secondary">Raw observations from automated checks</Typography>
          </Box>
          <Button variant="outlined" startIcon={<Add />}>Run Check</Button>
        </Box>

        {/* Table */}
        <Paper variant="outlined">
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ background: '#fafafa' }}>
                  <TableCell><strong>Severity</strong></TableCell>
                  <TableCell><strong>Finding</strong></TableCell>
                  <TableCell><strong>Site</strong></TableCell>
                  <TableCell><strong>Type</strong></TableCell>
                  <TableCell><strong>Detected</strong></TableCell>
                  <TableCell />
                </TableRow>
              </TableHead>
              <TableBody>
                {paginated.map((row) => (
                  <TableRow key={row.id} hover>
                    <TableCell>
                      <Chip label={row.severity} color={severityColors[row.severity]} size="small" sx={{ fontWeight: 600 }} />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" fontWeight={600}>{row.title}</Typography>
                      <Typography variant="caption" color="text.secondary">{row.description}</Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" fontWeight={600}>{row.siteName}</Typography>
                      <Typography variant="caption" color="text.secondary">{row.clientName}</Typography>
                    </TableCell>
                    <TableCell>
                      <Chip label={checkTypeLabels[row.checkType] || row.checkType} size="small" variant="outlined" />
                    </TableCell>
                    <TableCell>
                      <Typography variant="caption" color="text.secondary">
                        {formatDistanceToNow(row.detectedAt, { addSuffix: true })}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Tooltip title="View details">
                        <IconButton size="small"><OpenInNew fontSize="small" /></IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={mockFindings.length}
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
