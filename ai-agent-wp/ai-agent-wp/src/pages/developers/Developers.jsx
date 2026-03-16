import { useState, useMemo } from 'react';
import {
  Box, Button, Typography, Stack, Avatar, IconButton, Tooltip,
  Chip, Skeleton, Alert, Fab,
} from '@mui/material';

function ActionBtn({ title, icon: Icon, color, hoverBg, onClick }) {
  return (
    <Tooltip title={title}>
      <IconButton onClick={onClick} size="small" sx={{
        width: 30, height: 30, borderRadius: 1.5, color,
        transition: 'all 0.15s',
        '&:hover': { bgcolor: hoverBg, transform: 'scale(1.12)' },
      }}>
        <Icon sx={{ fontSize: 17 }} />
      </IconButton>
    </Tooltip>
  );
}
import { useNavigate } from 'react-router-dom';
import { Add, Edit, Visibility, Code, Delete } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import DataTable from '../../components/saas/DataTable';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_DEVELOPERS } from '../../graphql/queries';
import { DELETE_DEVELOPER } from '../../graphql/mutations';

const avatarColors = ['#8E43F0', '#0099C2', '#D97706', '#16A34A', '#DC2626', '#6A1FCC'];
const avatarColor = (name) => avatarColors[(name ?? '').charCodeAt(0) % avatarColors.length];

const STATUS_FILTERS = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

export default function Developers() {
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const { enqueueSnackbar } = useSnackbar();
  const { confirm } = useConfirmation();
  const [statusFilter, setStatusFilter] = useState('all');

  const { data, loading, error, refetch } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-and-network' });
  const devs = data?.developers ?? [];

  const [deleteDeveloper] = useMutation(DELETE_DEVELOPER, {
    onCompleted: () => { refetch(); enqueueSnackbar('Developer deleted', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to delete developer', { variant: 'error' }),
  });

  const handleDelete = async (id, name) => {
    const ok = await confirm({ title: 'Delete Developer', message: `Delete "${name}"? This cannot be undone.`, confirmText: 'Delete', severity: 'error' });
    if (ok) deleteDeveloper({ variables: { id } });
  };

  const filtered = useMemo(() =>
    statusFilter === 'all' ? devs : devs.filter((d) => d.status === statusFilter),
    [devs, statusFilter]
  );

  const rows = filtered.map((d) => ({
    ...d,
    specialization: d.company ?? d.role,
    skills: d.bio ? d.bio.split(',').map((s) => s.trim()).filter(Boolean) : [],
  }));

  const columns = [
    {
      key: 'name', label: 'Developer',
      render: (v, row) => (
        <Stack direction="row" alignItems="center" spacing={1.5}>
          <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
            <Avatar sx={{ width: 32, height: 32, bgcolor: avatarColor(v), fontSize: '0.72rem', fontWeight: 700, border: '2px solid #fff' }}>
              {v.split(' ').map((n) => n[0]).join('').slice(0, 2)}
            </Avatar>
          </Box>
          <Box>
            <Typography variant="body2" fontWeight={700}>{v}</Typography>
            <Typography variant="caption" color="text.secondary">{row.email}</Typography>
          </Box>
        </Stack>
      ),
    },
    {
      key: 'specialization', label: 'Role',
      render: (v) => (
        <Chip label={v ?? '—'} size="small" sx={{ bgcolor: '#EDE8FC', color: '#8E43F0', fontWeight: 600, height: 22 }} />
      ),
    },
    {
      key: 'skills', label: 'Skills',
      render: (skills) => (
        <Stack direction="row" spacing={0.5} flexWrap="wrap" gap={0.5}>
          {(skills ?? []).slice(0, 3).map((s) => (
            <Chip key={s} label={s} size="small" icon={<Code sx={{ fontSize: '12px!important' }} />}
              sx={{ height: 20, fontSize: '0.68rem', bgcolor: '#F7F5FF', border: '1px solid #DDD4F8' }} />
          ))}
          {(skills ?? []).length > 3 && (
            <Chip label={`+${skills.length - 3}`} size="small" sx={{ height: 20, fontSize: '0.68rem', bgcolor: '#EDE8FC', color: '#8E43F0' }} />
          )}
        </Stack>
      ),
    },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge value={v} /> },
    {
      key: 'actions', label: '', sortable: false,
      render: (_, row) => (
        <Stack direction="row" spacing={0.25}>
          <ActionBtn title="View" icon={Visibility} color="#8E43F0" hoverBg="#EDE8FC" onClick={() => navigate(`/developers/${row.id}`)} />
          {hasRole('SuperAdmin') && (
            <>
              <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/developers/${row.id}/edit`)} />
              <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(row.id, row.name)} />
            </>
          )}
        </Stack>
      ),
    },
  ];

  return (
    <Box sx={{ pb: { xs: 12, sm: 0 } }}>
      <PageHeader
        title="Developers"
        subtitle={loading ? 'Loading…' : `${filtered.length} of ${devs.length} team members`}
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Developers', path: '/developers' }]}
        action={hasRole('SuperAdmin') && (
          <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/developers/create')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
            Add Developer
          </Button>
        )}
      />

      {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>Failed to load developers. Please refresh.</Alert>}

      {/* Status filter chips */}
      <Box sx={{ display: 'flex', gap: 1, mb: 2 }}>
        {STATUS_FILTERS.map(({ label, value }) => (
          <Chip key={value} label={label} size="small" onClick={() => setStatusFilter(value)}
            sx={{
              fontWeight: 600, cursor: 'pointer',
              ...(statusFilter === value
                ? { bgcolor: 'primary.main', color: '#fff' }
                : { bgcolor: '#F7F5FF', color: 'text.secondary', border: '1px solid #DDD4F8' }),
            }}
          />
        ))}
      </Box>

      {loading && !data
        ? <Stack spacing={1.5}>{[1, 2, 3, 4].map((i) => <Skeleton key={i} variant="rounded" height={60} sx={{ borderRadius: 2 }} />)}</Stack>
        : <DataTable columns={columns} rows={rows} searchKeys={['name', 'email', 'specialization']} />
      }

      {hasRole('SuperAdmin') && (
        <Fab color="primary" onClick={() => navigate('/developers/create')} sx={{ position: 'fixed', bottom: 80, right: 16, display: { sm: 'none' } }}>
          <Add />
        </Fab>
      )}
    </Box>
  );
}
