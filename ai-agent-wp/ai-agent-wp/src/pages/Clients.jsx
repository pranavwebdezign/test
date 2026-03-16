import { useState } from 'react';
import {
  Box, Typography, Button, Chip,
  TextField, Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Paper, TablePagination,
  Dialog, DialogTitle, DialogContent, DialogActions,
  IconButton, InputAdornment, Stack,
} from '@mui/material';
import { Add, Search, Edit, Delete, Public } from '@mui/icons-material';
import { format } from 'date-fns';
import { useQuery, useMutation } from '@apollo/client';
import AppLayout from '../components/layout/AppLayout';
import { GET_WP_CLIENTS } from '../graphql/queries';
import AppSnackbar from '../components/AppSnackbar';
import ConfirmDialog from '../components/ConfirmDialog';
import { useNotification } from '../hooks/useNotification';

const healthColors = {
  healthy: 'success',
  warning: 'warning',
  critical: 'error',
  unknown: 'default',
};

export default function Clients() {
  const [searchQuery, setSearchQuery] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingClient, setEditingClient] = useState(null);
  const [formValues, setFormValues] = useState({ name: '', slug: '' });
  const [formErrors, setFormErrors] = useState({});
  const [page, setPage] = useState(0);
  const [rowsPerPage] = useState(10);
  const [confirmDialog, setConfirmDialog] = useState({ open: false, id: null });
  const { notify, snackbarProps } = useNotification();
  
  const { data, loading, error, refetch } = useQuery(GET_WP_CLIENTS);
  
  const getClientSitesCount = (clientId) => {
    // In a full implementation, you'd fetch this from the backend. 
    // We'll rely on sites_count from the client object if available
    const client = data?.wpClients?.find((c) => c.id === clientId);
    return client?.sites_count || 0;
  };

  const getClientHealth = (clientId) => {
    // Hard to determine without querying sites. Default to unknown for now
    return 'unknown';
  };

  const clients = data?.wpClients || [];

  const filteredClients = clients.filter(
    (c) =>
      c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.slug.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const paginated = filteredClients.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

  const handleAdd = () => {
    setEditingClient(null);
    setFormValues({ name: '', slug: '' });
    setFormErrors({});
    setIsModalOpen(true);
  };

  const handleEdit = (client) => {
    setEditingClient(client);
    setFormValues({ name: client.name, slug: client.slug });
    setFormErrors({});
    setIsModalOpen(true);
  };

  const handleDeleteConfirm = (id) => setConfirmDialog({ open: true, id });

  const handleDelete = () => {
    // TODO: implement actual delete mutation here
    notify('Client deleted (Mocked until API complete)', 'success');
  };

  const validateForm = () => {
    const errors = {};
    if (!formValues.name.trim()) errors.name = 'Client name is required';
    if (!formValues.slug.trim()) errors.slug = 'Slug is required';
    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSave = () => {
    if (!validateForm()) return;
    if (editingClient) {
      // TODO: implement actual update mutation here
      notify('Client updated (Mocked until API complete)', 'success');
    } else {
      // TODO: implement actual create mutation here
      notify('Client added (Mocked until API complete)', 'success');
    }
    setIsModalOpen(false);
  };

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Clients</Typography>
            <Typography variant="body2" color="text.secondary">Manage your client accounts</Typography>
          </Box>
          <Button variant="contained" startIcon={<Add />} onClick={handleAdd}>Add Client</Button>
        </Box>

        <Paper variant="outlined">
          <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
            <TextField
              placeholder="Search clients..."
              size="small"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              InputProps={{ startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> }}
              sx={{ width: { xs: '100%', sm: 300 } }}
            />
          </Box>

          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ background: '#fafafa' }}>
                  <TableCell><strong>Client</strong></TableCell>
                  <TableCell><strong>Sites</strong></TableCell>
                  <TableCell><strong>Health</strong></TableCell>
                  <TableCell><strong>Created</strong></TableCell>
                  <TableCell><strong>Actions</strong></TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {paginated.map((row) => {
                  const health = getClientHealth(row.id);
                  return (
                    <TableRow key={row.id} hover>
                      <TableCell>
                        <Typography variant="body2" fontWeight={600}>{row.name}</Typography>
                        <Typography variant="caption" color="text.secondary">@{row.slug}</Typography>
                      </TableCell>
                      <TableCell>
                        <Stack direction="row" alignItems="center" spacing={0.5}>
                          <Public fontSize="small" color="action" />
                          <Typography variant="body2">{getClientSitesCount(row.id)}</Typography>
                        </Stack>
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={health.charAt(0).toUpperCase() + health.slice(1)}
                          color={healthColors[health]}
                          size="small"
                          variant="outlined"
                        />
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.created_at ? format(new Date(row.created_at), 'MMM d, yyyy') : '—'}</Typography>
                      </TableCell>
                      <TableCell>
                        <Stack direction="row" spacing={0.5}>
                          <IconButton size="small" onClick={() => handleEdit(row)}><Edit fontSize="small" /></IconButton>
                          <IconButton size="small" color="error" onClick={() => handleDeleteConfirm(row.id)}><Delete fontSize="small" /></IconButton>
                        </Stack>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={filteredClients.length}
            page={page}
            onPageChange={(_, p) => setPage(p)}
            rowsPerPage={rowsPerPage}
            rowsPerPageOptions={[10]}
          />
        </Paper>

        {/* Add/Edit Dialog */}
        <Dialog open={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="xs" fullWidth>
          <DialogTitle>{editingClient ? 'Edit Client' : 'Add Client'}</DialogTitle>
          <DialogContent sx={{ pt: '16px !important' }}>
            <Stack spacing={2.5}>
              <TextField
                label="Client Name"
                placeholder="e.g., Acme Corporation"
                value={formValues.name}
                onChange={(e) => setFormValues((p) => ({ ...p, name: e.target.value }))}
                error={!!formErrors.name}
                helperText={formErrors.name}
                fullWidth
                required
              />
              <TextField
                label="Slug"
                placeholder="e.g., acme"
                value={formValues.slug}
                onChange={(e) => setFormValues((p) => ({ ...p, slug: e.target.value }))}
                error={!!formErrors.slug}
                helperText={formErrors.slug}
                fullWidth
                required
              />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setIsModalOpen(false)}>Cancel</Button>
            <Button variant="contained" onClick={handleSave}>{editingClient ? 'Save' : 'Add'}</Button>
          </DialogActions>
        </Dialog>

        <ConfirmDialog
          open={confirmDialog.open}
          title="Delete Client"
          content="Are you sure you want to delete this client? This action cannot be undone."
          okText="Delete"
          danger
          onOk={handleDelete}
          onClose={() => setConfirmDialog((p) => ({ ...p, open: false }))}
        />
        <AppSnackbar {...snackbarProps} />
      </Box>
    </AppLayout>
  );
}
