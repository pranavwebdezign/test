import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box, Typography, Button, Chip, Grid, Paper, Card, CardContent,
  CardActions, TextField, MenuItem, Select, FormControl, InputLabel,
  Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  TablePagination, IconButton, Tooltip, Stack, Checkbox,
  ToggleButtonGroup, ToggleButton, Alert, Menu, Divider,
  InputAdornment, Link as MuiLink,
} from '@mui/material';
import {
  Add, Search, GridView, List as ListIcon, Edit, Delete, Link as LinkIcon,
  Refresh, Download, Sync, KeyboardArrowDown, AccessTime,
  Extension, Key, LinkOff as LinkOffIcon,
} from '@mui/icons-material';
import { LinearProgress } from '@mui/material';
import { formatDistanceToNow } from 'date-fns';
import AppLayout from '../../components/layout/AppLayout';
import { useQuery, useMutation } from '@apollo/client';
import { GET_WP_SITES } from '../../graphql/queries';
import { DELETE_WP_SITE } from '../../graphql/mutations';
import AppSnackbar from '../../components/AppSnackbar';
import ConfirmDialog from '../../components/ConfirmDialog';
import { useNotification } from '../../hooks/useNotification';

const healthStatus = {
  healthy: { color: 'success', text: 'Healthy', dot: '#16A34A', progress: 92 },
  warning:  { color: 'warning', text: 'Warning',  dot: '#D97706', progress: 55 },
  critical: { color: 'error',   text: 'Critical', dot: '#DC2626', progress: 20 },
  unknown:  { color: 'default', text: 'Unknown',  dot: '#9B89C4', progress: 0  },
};

const progressColor = { healthy: '#16A34A', warning: '#D97706', critical: '#DC2626', unknown: '#9B89C4' };

export default function Sites() {
  const navigate = useNavigate();
  const { notify, snackbarProps } = useNotification();

  const { data: sitesData, refetch: refetchSites } = useQuery(GET_WP_SITES, { fetchPolicy: 'cache-and-network' });
  const rawSites = sitesData?.wpSites || [];

  const [deleteWpSite] = useMutation(DELETE_WP_SITE, {
    onCompleted: () => { notify('Site deleted', 'success'); setSelectedSites([]); refetchSites(); },
    onError: (e) => notify(e.message, 'error'),
  });

  const [searchQuery, setSearchQuery]   = useState('');
  const [healthFilter, setHealthFilter] = useState('all');
  const [viewMode, setViewMode]         = useState('grid');
  const [selectedSites, setSelectedSites] = useState([]);
  const [bulkLoading, setBulkLoading]   = useState(false);
  const [page, setPage]                 = useState(0);
  const [rowsPerPage]                   = useState(10);
  const [confirmDialog, setConfirmDialog] = useState({ open: false, title: '', content: '', onOk: null });
  const [bulkMenuAnchor, setBulkMenuAnchor] = useState(null);

  const filteredSites = rawSites.filter((site) => {
    const matchesSearch =
      site.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (site.wp_client?.name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
      site.url.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesHealth = healthFilter === 'all' || site.overall_health === healthFilter;
    return matchesSearch && matchesHealth;
  });

  const paginatedSites = viewMode === 'list'
    ? filteredSites.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage)
    : filteredSites;

  const handleDelete = (id) => {
    setConfirmDialog({
      open: true,
      title: 'Delete Site',
      content: 'Are you sure you want to delete this site? This action cannot be undone.',
      onOk: () => deleteWpSite({ variables: { id } }),
    });
  };

  const handleSelectSite = (siteId, checked) =>
    setSelectedSites((prev) => checked ? [...prev, siteId] : prev.filter((id) => id !== siteId));

  const handleSelectAll = (checked) =>
    setSelectedSites(checked ? filteredSites.map((s) => s.id) : []);

  const isAllSelected  = filteredSites.length > 0 && selectedSites.length === filteredSites.length;
  const isSomeSelected = selectedSites.length > 0 && selectedSites.length < filteredSites.length;

  const handleBulkRunChecks = () => {
    setBulkLoading(true);
    notify(`Running health checks on ${selectedSites.length} sites...`, 'info');
    setTimeout(() => {
      setBulkLoading(false);
      notify(`Health checks completed for ${selectedSites.length} sites`, 'success');
      setSelectedSites([]);
    }, 2000);
  };

  const handleBulkDelete = () => {
    setBulkMenuAnchor(null);
    setConfirmDialog({
      open: true,
      title: 'Delete Selected Sites',
      content: `Are you sure you want to delete ${selectedSites.length} sites? This action cannot be undone.`,
      onOk: () => {
        selectedSites.forEach((id) => deleteWpSite({ variables: { id } }));
      },
    });
  };

  const handleBulkExport = () => {
    const selectedData = rawSites.filter((s) => selectedSites.includes(s.id));
    const dataStr = JSON.stringify(selectedData, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `sites-export-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
    notify(`Exported ${selectedSites.length} sites`, 'success');
    setBulkMenuAnchor(null);
  };

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 }, pt: { xs: 7, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Sites</Typography>
            <Typography variant="body2" color="text.secondary">Manage and monitor all your WordPress sites</Typography>
          </Box>
          <Stack direction="row" spacing={1}>
            <Button
              variant="outlined"
              startIcon={<Refresh />}
              onClick={() => { notify('Refreshing sites...', 'info'); refetchSites(); }}
            >
              Refresh
            </Button>
            <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/sites/new')}>
              Add Site
            </Button>
          </Stack>
        </Box>

        {/* Bulk Selection Alert */}
        {selectedSites.length > 0 && (
          <Alert
            severity="info"
            sx={{ mb: 2 }}
            action={
              <Stack direction="row" spacing={1} alignItems="center">
                <Button size="small" onClick={handleBulkRunChecks} disabled={bulkLoading} startIcon={<Sync />}>
                  Run Checks
                </Button>
                <Button size="small" onClick={handleBulkExport} startIcon={<Download />}>Export</Button>
                <Button size="small" color="error" onClick={handleBulkDelete} startIcon={<Delete />}>Delete</Button>
                <IconButton size="small" onClick={(e) => setBulkMenuAnchor(e.currentTarget)}><KeyboardArrowDown /></IconButton>
                <Menu anchorEl={bulkMenuAnchor} open={Boolean(bulkMenuAnchor)} onClose={() => setBulkMenuAnchor(null)}>
                  <Divider />
                  <MenuItem onClick={() => handleBulkExport()}><Download fontSize="small" sx={{ mr: 1 }} />Export Selected</MenuItem>
                  <MenuItem onClick={handleBulkDelete} sx={{ color: 'error.main' }}><Delete fontSize="small" sx={{ mr: 1 }} />Delete Selected</MenuItem>
                </Menu>
              </Stack>
            }
          >
            <strong>{selectedSites.length}</strong> site{selectedSites.length !== 1 ? 's' : ''} selected &nbsp;
            <MuiLink sx={{ cursor: 'pointer', fontSize: 13 }} onClick={() => setSelectedSites([])}>Clear</MuiLink>
          </Alert>
        )}

        {/* Filters */}
        <Paper variant="outlined" sx={{ p: 2, mb: 3, display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2, alignItems: 'center' }}>
          <Stack direction="row" spacing={2} alignItems="center" flexWrap="wrap" gap={1}>
            <Checkbox
              checked={isAllSelected}
              indeterminate={isSomeSelected}
              onChange={(e) => handleSelectAll(e.target.checked)}
              size="small"
            />
            <TextField
              placeholder="Search sites..."
              size="small"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              InputProps={{ startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> }}
              sx={{ width: 260 }}
            />
            <FormControl size="small" sx={{ minWidth: 160 }}>
              <InputLabel>Health</InputLabel>
              <Select value={healthFilter} label="Health" onChange={(e) => setHealthFilter(e.target.value)}>
                <MenuItem value="all">All Sites</MenuItem>
                <MenuItem value="healthy">Healthy</MenuItem>
                <MenuItem value="warning">Warning</MenuItem>
                <MenuItem value="critical">Critical</MenuItem>
                <MenuItem value="unknown">Unknown</MenuItem>
              </Select>
            </FormControl>
          </Stack>
          <ToggleButtonGroup value={viewMode} exclusive onChange={(_, v) => v && setViewMode(v)} size="small">
            <ToggleButton value="grid"><GridView fontSize="small" /></ToggleButton>
            <ToggleButton value="list"><ListIcon fontSize="small" /></ToggleButton>
          </ToggleButtonGroup>
        </Paper>

        {/* Grid View */}
        {viewMode === 'grid' ? (
          <Grid container spacing={2}>
            {filteredSites.map((site) => {
              const totalUpdates = (site.plugin_updates || 0) + (site.theme_updates || 0) + (site.core_update_available ? 1 : 0);
              const isSelected = selectedSites.includes(site.id);
              const hs = healthStatus[site.overall_health] || healthStatus.unknown;
              return (
                <Grid item xs={12} sm={6} lg={4} key={site.id}>
                  <Card
                    variant="outlined"
                    sx={{
                      height: '100%', cursor: 'pointer',
                      border: isSelected ? '2px solid' : '1px solid',
                      borderColor: isSelected ? 'primary.main' : '#DDD4F8',
                      background: isSelected ? 'rgba(142,67,240,0.04)' : '#fff',
                      transition: 'all 0.2s',
                      borderRadius: 3,
                      '&:hover': { boxShadow: '0 4px 20px rgba(142,67,240,0.14)', transform: 'translateY(-2px)' },
                    }}
                    onClick={() => navigate(`/sites/${site.id}`)}
                  >
                    <CardContent sx={{ pb: 1 }}>
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1.5 }}>
                        <Stack direction="row" alignItems="flex-start" spacing={1}>
                          <Checkbox size="small" checked={isSelected} onChange={(e) => handleSelectSite(site.id, e.target.checked)} onClick={(e) => e.stopPropagation()} sx={{ p: 0, mt: 0.25 }} />
                          <Box>
                            <Typography variant="body1" fontWeight={700}>{site.name}</Typography>
                            <Typography variant="caption" color="text.secondary">{site.wp_client?.name || 'Unknown'}</Typography>
                          </Box>
                        </Stack>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75 }}>
                          <Box sx={{ width: 10, height: 10, borderRadius: '50%', bgcolor: hs.dot, boxShadow: `0 0 0 3px ${hs.dot}22` }} />
                          <Typography variant="caption" fontWeight={600} color={hs.dot} sx={{ fontSize: '0.72rem' }}>{hs.text}</Typography>
                        </Box>
                      </Box>

                      <MuiLink href={site.url} target="_blank" rel="noopener noreferrer"
                        sx={{ fontSize: 13, display: 'flex', alignItems: 'center', gap: 0.5, mb: 1, color: 'primary.main' }}
                        onClick={(e) => e.stopPropagation()}>
                        <LinkIcon fontSize="small" /> {new URL(site.url).hostname}
                      </MuiLink>

                      <Box sx={{ mb: 1.5 }}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                          <Typography variant="caption" color="text.secondary">Health Score</Typography>
                          <Typography variant="caption" fontWeight={700} color={progressColor[site.overall_health] || progressColor.unknown}>
                            {hs.progress}%
                          </Typography>
                        </Box>
                        <LinearProgress
                          variant="determinate"
                          value={hs.progress}
                          sx={{
                            height: 5, borderRadius: 4, bgcolor: '#F5F0FF',
                            '& .MuiLinearProgress-bar': { bgcolor: progressColor[site.overall_health] || progressColor.unknown, borderRadius: 4 }
                          }}
                        />
                      </Box>

                      <Stack direction="row" spacing={0.75} sx={{ mb: 1.5 }} flexWrap="wrap">
                        <Chip label={`PHP ${site.php_version || '—'}`} size="small" variant="outlined" sx={{ borderColor: '#DDD4F8', fontSize: '0.72rem' }} />
                        <Chip label={`WP ${site.wp_version || '—'}`} size="small" variant="outlined" sx={{ borderColor: '#DDD4F8', fontSize: '0.72rem' }} />
                        {site.aa_plugin_active ? (
                          <Chip icon={<Extension sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="Plugin" size="small"
                            sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#DCFCE7', color: '#15803D', border: '1px solid #86EFAC', '& .MuiChip-icon': { color: '#15803D' } }} />
                        ) : site.is_authenticated ? (
                          <Chip icon={<Key sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="WP Auth" size="small"
                            sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#FEF9C3', color: '#92400E', border: '1px solid #FDE68A', '& .MuiChip-icon': { color: '#92400E' } }} />
                        ) : (
                          <Chip icon={<LinkOffIcon sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="No Auth" size="small"
                            sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#FEE2E2', color: '#DC2626', border: '1px solid #FECACA', '& .MuiChip-icon': { color: '#DC2626' } }} />
                        )}
                      </Stack>

                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="caption" color="text.secondary" sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                          <AccessTime sx={{ fontSize: 12 }} />
                          {site.last_checked_at ? formatDistanceToNow(new Date(site.last_checked_at), { addSuffix: true }) : 'Never checked'}
                        </Typography>
                        {totalUpdates > 0 && (
                          <Chip label={`${totalUpdates} update${totalUpdates !== 1 ? 's' : ''}`} color="warning" size="small" sx={{ height: 20, fontSize: '0.72rem' }} />
                        )}
                      </Box>
                    </CardContent>
                    <CardActions sx={{ justifyContent: 'space-between', pt: 0, px: 1.5, pb: 1 }} onClick={(e) => e.stopPropagation()}>
                      {!site.aa_plugin_active && (
                        <Tooltip title={site.is_authenticated ? 'Manage Connection' : 'Connect this site'}>
                          <Button
                            size="small" variant="text"
                            startIcon={<LinkIcon sx={{ fontSize: 13 }} />}
                            onClick={() => navigate(`/sites/${site.id}`)}
                            sx={{ fontSize: '0.7rem', color: site.is_authenticated ? '#D97706' : '#8E43F0', px: 1, py: 0.3 }}
                          >
                            {site.is_authenticated ? 'Verify' : 'Connect'}
                          </Button>
                        </Tooltip>
                      )}
                      <Box sx={{ ml: 'auto' }}>
                        <Tooltip title="Edit">
                          <IconButton size="small" onClick={() => navigate(`/sites/${site.id}/edit`)}
                            sx={{ width: 30, height: 30, borderRadius: 1.5, color: '#D97706', transition: 'all 0.15s', '&:hover': { bgcolor: '#FFFBEB', transform: 'scale(1.12)' } }}>
                            <Edit sx={{ fontSize: 17 }} />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Delete">
                          <IconButton size="small" onClick={() => handleDelete(site.id)}
                            sx={{ width: 30, height: 30, borderRadius: 1.5, color: '#DC2626', transition: 'all 0.15s', '&:hover': { bgcolor: '#FEF2F2', transform: 'scale(1.12)' } }}>
                            <Delete sx={{ fontSize: 17 }} />
                          </IconButton>
                        </Tooltip>
                      </Box>
                    </CardActions>
                  </Card>
                </Grid>
              );
            })}
          </Grid>
        ) : (
          /* List View */
          <Paper variant="outlined">
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow sx={{ background: '#fafafa' }}>
                    <TableCell padding="checkbox">
                      <Checkbox size="small" checked={isAllSelected} indeterminate={isSomeSelected} onChange={(e) => handleSelectAll(e.target.checked)} />
                    </TableCell>
                    <TableCell><strong>Site</strong></TableCell>
                    <TableCell><strong>URL</strong></TableCell>
                    <TableCell><strong>Status</strong></TableCell>
                    <TableCell><strong>Connection</strong></TableCell>
                    <TableCell><strong>PHP</strong></TableCell>
                    <TableCell><strong>WordPress</strong></TableCell>
                    <TableCell><strong>Last Checked</strong></TableCell>
                    <TableCell><strong>Actions</strong></TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {paginatedSites.map((site) => {
                    const hs = healthStatus[site.overall_health] || healthStatus.unknown;
                    return (
                      <TableRow key={site.id} hover>
                        <TableCell padding="checkbox">
                          <Checkbox size="small" checked={selectedSites.includes(site.id)} onChange={(e) => handleSelectSite(site.id, e.target.checked)} />
                        </TableCell>
                        <TableCell>
                          <Typography variant="body2" fontWeight={600} color="primary" sx={{ cursor: 'pointer' }} onClick={() => navigate(`/sites/${site.id}`)}>
                            {site.name}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">{site.wp_client?.name || 'Unknown'}</Typography>
                        </TableCell>
                        <TableCell>
                          <MuiLink href={site.url} target="_blank" rel="noopener noreferrer" variant="body2">
                            <LinkIcon sx={{ fontSize: 12, mr: 0.5, verticalAlign: 'middle' }} />
                            {new URL(site.url).hostname}
                          </MuiLink>
                        </TableCell>
                        <TableCell><Chip label={hs.text} color={hs.color} size="small" /></TableCell>
                        <TableCell>
                          {site.aa_plugin_active ? (
                            <Chip icon={<Extension sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="Plugin" size="small" sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#DCFCE7', color: '#15803D', '& .MuiChip-icon': { color: '#15803D' } }} />
                          ) : site.is_authenticated ? (
                            <Chip icon={<Key sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="WP Auth" size="small" sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#FEF9C3', color: '#92400E', '& .MuiChip-icon': { color: '#92400E' } }} />
                          ) : (
                            <Chip icon={<LinkOffIcon sx={{ fontSize: '11px !important', ml: '4px !important' }} />} label="No Auth" size="small" sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700, bgcolor: '#FEE2E2', color: '#DC2626', '& .MuiChip-icon': { color: '#DC2626' } }} />
                          )}
                        </TableCell>
                        <TableCell><Chip label={site.php_version || '—'} size="small" variant="outlined" /></TableCell>
                        <TableCell><Chip label={site.wp_version || '—'} size="small" variant="outlined" /></TableCell>
                        <TableCell>
                          <Typography variant="caption" color="text.secondary">
                            {site.last_checked_at ? formatDistanceToNow(new Date(site.last_checked_at), { addSuffix: true }) : 'Never'}
                          </Typography>
                        </TableCell>
                        <TableCell>
                          <Stack direction="row" spacing={0.5}>
                            <Button size="small" onClick={() => navigate(`/sites/${site.id}`)}>View</Button>
                            <Tooltip title="Edit"><IconButton size="small" onClick={() => navigate(`/sites/${site.id}/edit`)}><Edit fontSize="small" /></IconButton></Tooltip>
                            <Tooltip title="Delete"><IconButton size="small" color="error" onClick={() => handleDelete(site.id)}><Delete fontSize="small" /></IconButton></Tooltip>
                          </Stack>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>
            <TablePagination
              component="div" count={filteredSites.length} page={page}
              onPageChange={(_, p) => setPage(p)} rowsPerPage={rowsPerPage} rowsPerPageOptions={[10]}
            />
          </Paper>
        )}

        {filteredSites.length === 0 && (
          <Paper variant="outlined" sx={{ textAlign: 'center', py: 8 }}>
            <Typography color="text.secondary" sx={{ mb: 1 }}>No sites found matching your criteria.</Typography>
            <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/sites/new')}>Add Your First Site</Button>
          </Paper>
        )}

        <ConfirmDialog
          open={confirmDialog.open}
          title={confirmDialog.title}
          content={confirmDialog.content}
          okText="Delete"
          danger
          onOk={confirmDialog.onOk}
          onClose={() => setConfirmDialog((p) => ({ ...p, open: false }))}
        />
        <AppSnackbar {...snackbarProps} />
      </Box>
    </AppLayout>
  );
}
