import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box, Typography, Button, Chip,
  TextField, MenuItem, Select, FormControl, InputLabel,
  Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Paper, TablePagination,
  Tabs, Tab, Tooltip, InputAdornment, Avatar, Link as MuiLink,
  CircularProgress,
} from '@mui/material';
import { Add, Search } from '@mui/icons-material';
import { formatDistanceToNow } from 'date-fns';
import { useQuery, useMutation } from '@apollo/client';
import AppLayout from '../components/layout/AppLayout';
import { GET_WP_WORK_ITEMS, UPDATE_WP_WORK_ITEM } from '../graphql/queries';

const severityColors = {
  P0: 'error',
  P1: 'warning',
  P2: 'info',
  P3: 'default',
};

function TabPanel({ children, value, index }) {
  return value === index ? <>{children}</> : null;
}

export default function WorkQueue() {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState('');
  const [tabFilter, setTabFilter] = useState(1);
  const [severityFilter, setSeverityFilter] = useState('all');
  const [page, setPage] = useState(0);
  const [rowsPerPage] = useState(10);

  const { data, loading, refetch } = useQuery(GET_WP_WORK_ITEMS, {
    fetchPolicy: 'cache-and-network',
  });
  const workItems = data?.wpWorkItems ?? [];

  const [updateWorkItem] = useMutation(UPDATE_WP_WORK_ITEM, {
    onCompleted: () => refetch(),
  });

  const handleStatusChange = (id, status) => {
    updateWorkItem({ variables: { id, status } });
  };

  const activeCount = workItems.filter((w) => ['open', 'acknowledged', 'in_progress'].includes(w.status)).length;
  const doneCount = workItems.filter((w) => ['done', 'not_applicable'].includes(w.status)).length;

  const filteredItems = workItems.filter((item) => {
    const matchesSearch =
      item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (item.site?.name ?? '').toLowerCase().includes(searchQuery.toLowerCase()) ||
      (item.site?.wp_client?.name ?? '').toLowerCase().includes(searchQuery.toLowerCase());

    const matchesTab =
      tabFilter === 0 ||
      (tabFilter === 1 && ['open', 'acknowledged', 'in_progress'].includes(item.status)) ||
      (tabFilter === 2 && ['done', 'not_applicable'].includes(item.status));

    const matchesSeverity = severityFilter === 'all' || item.severity === severityFilter;

    return matchesSearch && matchesTab && matchesSeverity;
  });

  const paginated = filteredItems.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Work Queue</Typography>
            <Typography variant="body2" color="text.secondary">Track and manage remediation tasks</Typography>
          </Box>
          <Button variant="contained" startIcon={<Add />}>Create Task</Button>
        </Box>

        <Paper variant="outlined">
          {/* Tabs + Filters */}
          <Box sx={{ borderBottom: 1, borderColor: 'divider', px: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 1 }}>
            <Tabs value={tabFilter} onChange={(_, v) => { setTabFilter(v); setPage(0); }}>
              <Tab label={`All (${workItems.length})`} />
              <Tab label={`Active (${activeCount})`} />
              <Tab label={`Done (${doneCount})`} />
            </Tabs>
            <Box sx={{ display: 'flex', gap: 1.5, py: 1, flexWrap: 'wrap' }}>
              <TextField
                placeholder="Search tasks..."
                size="small"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                InputProps={{ startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> }}
                sx={{ width: 220 }}
              />
              <FormControl size="small" sx={{ minWidth: 150 }}>
                <InputLabel>Severity</InputLabel>
                <Select value={severityFilter} label="Severity" onChange={(e) => { setSeverityFilter(e.target.value); setPage(0); }}>
                  <MenuItem value="all">All Severity</MenuItem>
                  <MenuItem value="P0">P0 - Critical</MenuItem>
                  <MenuItem value="P1">P1 - High</MenuItem>
                  <MenuItem value="P2">P2 - Medium</MenuItem>
                  <MenuItem value="P3">P3 - Low</MenuItem>
                </Select>
              </FormControl>
            </Box>
          </Box>

          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ background: '#fafafa' }}>
                  <TableCell><strong>Severity</strong></TableCell>
                  <TableCell><strong>Task</strong></TableCell>
                  <TableCell><strong>Created</strong></TableCell>
                  <TableCell><strong>Status</strong></TableCell>
                  <TableCell><strong>Assignee</strong></TableCell>
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
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, flexWrap: 'wrap' }}>
                        <MuiLink
                          component="button"
                          variant="caption"
                          onClick={() => navigate(`/sites/${row.site?.id}`)}
                          sx={{ fontWeight: 600, cursor: 'pointer' }}
                        >
                          {row.site?.name ?? '—'}
                        </MuiLink>
                        {row.site?.wp_client?.name && (
                          <Typography variant="caption" color="text.secondary">
                            • {row.site.wp_client.name}
                          </Typography>
                        )}
                      </Box>
                      {row.description && (
                        <Typography variant="caption" color="text.secondary" display="block">{row.description}</Typography>
                      )}
                    </TableCell>
                    <TableCell>
                      <Typography variant="caption" color="text.secondary">
                        {row.created_at ? formatDistanceToNow(new Date(row.created_at), { addSuffix: true }) : '—'}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Select
                        value={row.status}
                        onChange={(e) => handleStatusChange(row.id, e.target.value)}
                        size="small"
                        sx={{ width: 150, fontSize: 13 }}
                      >
                        <MenuItem value="open">Open</MenuItem>
                        <MenuItem value="acknowledged">Acknowledged</MenuItem>
                        <MenuItem value="in_progress">In Progress</MenuItem>
                        <MenuItem value="done">Done</MenuItem>
                        <MenuItem value="not_applicable">N/A</MenuItem>
                      </Select>
                    </TableCell>
                    <TableCell>
                      {row.assignee?.name ? (
                        <Tooltip title={row.assignee.name}>
                          <Avatar sx={{ width: 28, height: 28, fontSize: 11, bgcolor: 'primary.main' }}>
                            {row.assignee.name.split(' ').map((n) => n[0]).join('')}
                          </Avatar>
                        </Tooltip>
                      ) : (
                        <Typography variant="caption" color="text.disabled">—</Typography>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={filteredItems.length}
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
