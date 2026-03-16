import { useState, useMemo } from 'react';
import {
    Box, Typography, Card, CardContent, Stack, Chip, Divider, CircularProgress,
    ToggleButtonGroup, ToggleButton, TextField, InputAdornment, Button, IconButton,
    Tooltip, Alert,
} from '@mui/material';
import {
    NotificationsOutlined, DoneAll, Search, Circle, FiberManualRecord,
} from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import { GET_NOTIFICATIONS } from '../../graphql/queries';
import { MARK_NOTIFICATION_READ } from '../../graphql/mutations';
import { fmtDatetime, fmtDate } from '../../utils/dates';

const TYPE_COLORS = {
    info: { bg: '#EDE8FC', text: '#8E43F0', label: 'Info' },
    success: { bg: '#F0FDF4', text: '#16A34A', label: 'Success' },
    warning: { bg: '#FFFBEB', text: '#D97706', label: 'Warning' },
    error: { bg: '#FEF2F2', text: '#DC2626', label: 'Error' },
    task: { bg: '#F5F3FF', text: '#8E43F0', label: 'Task' },
    project: { bg: '#ECFEFF', text: '#0099C2', label: 'Project' },
};

const TYPES = ['All', 'info', 'success', 'warning', 'error', 'task', 'project'];

function timeAgo(iso) {
    if (!iso) return '';
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return fmtDatetime(iso);
}

export default function Notifications() {
    const { enqueueSnackbar } = useSnackbar();
    const [filter, setFilter] = useState('all');    // 'all' | 'unread' | 'read'
    const [typeFilter, setType] = useState('All');
    const [search, setSearch] = useState('');

    const { data, loading, error, refetch } = useQuery(GET_NOTIFICATIONS, {
        fetchPolicy: 'cache-and-network',
        pollInterval: 60000,
    });

    const notifications = data?.notifications ?? [];

    const [markRead, { loading: markingOne }] = useMutation(MARK_NOTIFICATION_READ, {
        onCompleted: () => refetch(),
        onError: (e) => enqueueSnackbar(e.message || 'Failed to mark as read', { variant: 'error' }),
    });

    const [markingAll, setMarkingAll] = useState(false);
    const handleMarkAll = async () => {
        const unread = notifications.filter((n) => !n.read_at);
        if (!unread.length) return;
        setMarkingAll(true);
        try {
            await Promise.all(unread.map((n) => markRead({ variables: { id: n.id } })));
            refetch();
            enqueueSnackbar('All notifications marked as read', { variant: 'success' });
        } catch (e) {
            enqueueSnackbar(e.message || 'Failed', { variant: 'error' });
        } finally {
            setMarkingAll(false);
        }
    };

    const filtered = useMemo(() => {
        return notifications.filter((n) => {
            if (filter === 'unread' && n.read_at) return false;
            if (filter === 'read' && !n.read_at) return false;
            if (typeFilter !== 'All' && n.type !== typeFilter) return false;
            if (search && !n.message?.toLowerCase().includes(search.toLowerCase())) return false;
            return true;
        });
    }, [notifications, filter, typeFilter, search]);

    const unreadCount = notifications.filter((n) => !n.read_at).length;

    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load notifications.</Alert>;

    return (
        <Box>
            <PageHeader
                title="Notifications"
                subtitle={`${unreadCount} unread · ${notifications.length} total`}
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Notifications' }]}
                action={
                    <Stack direction="row" spacing={1.5} alignItems="center">
                        {unreadCount > 0 && (
                            <Box sx={{ px: 1.5, py: 0.5, borderRadius: 10, bgcolor: '#EDE8FC', border: '1px solid #D4B8FA' }}>
                                <Typography variant="caption" fontWeight={800} color="primary.main">
                                    {unreadCount} unread
                                </Typography>
                            </Box>
                        )}
                        {unreadCount > 0 && (
                            <Button
                                variant="outlined"
                                startIcon={markingAll ? <CircularProgress size={14} /> : <DoneAll />}
                                onClick={() => handleMarkAll()}
                                disabled={markingAll}
                                size="small"
                                sx={{ borderRadius: 2 }}
                            >
                                Mark all read
                            </Button>
                        )}
                    </Stack>
                }
            />

            {/* Filters */}
            <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', mb: 3 }}>
                <CardContent sx={{ p: 2, '&:last-child': { pb: 2 } }}>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems={{ sm: 'center' }}>
                        {/* Read/Unread filter */}
                        <ToggleButtonGroup
                            value={filter}
                            exclusive
                            onChange={(_, v) => v && setFilter(v)}
                            size="small"
                            sx={{ '& .MuiToggleButton-root': { px: 2, fontSize: '0.8rem', textTransform: 'none', borderRadius: 2 } }}
                        >
                            <ToggleButton value="all">All ({notifications.length})</ToggleButton>
                            <ToggleButton value="unread">Unread ({unreadCount})</ToggleButton>
                            <ToggleButton value="read">Read ({notifications.length - unreadCount})</ToggleButton>
                        </ToggleButtonGroup>

                        {/* Type filter */}
                        <Stack direction="row" spacing={0.75} flexWrap="wrap" gap={0.75}>
                            {TYPES.map((t) => {
                                const meta = TYPE_COLORS[t];
                                const active = typeFilter === t;
                                return (
                                    <Chip
                                        key={t}
                                        label={meta?.label ?? t}
                                        size="small"
                                        onClick={() => setType(t)}
                                        sx={{
                                            fontWeight: 600, fontSize: '0.75rem', cursor: 'pointer',
                                            bgcolor: active ? (meta?.bg ?? '#EDE8FC') : '#F7F5FF',
                                            color: active ? (meta?.text ?? '#8E43F0') : 'text.secondary',
                                            border: active ? `1.5px solid ${meta?.text ?? '#8E43F0'}` : '1.5px solid #DDD4F8',
                                            '&:hover': { bgcolor: meta?.bg ?? '#EDE8FC', color: meta?.text ?? '#8E43F0' },
                                        }}
                                    />
                                );
                            })}
                        </Stack>

                        {/* Search */}
                        <TextField
                            size="small" placeholder="Search…"
                            value={search} onChange={(e) => setSearch(e.target.value)}
                            InputProps={{ startAdornment: <InputAdornment position="start"><Search fontSize="small" color="disabled" /></InputAdornment> }}
                            sx={{ ml: 'auto', minWidth: 180, '& .MuiOutlinedInput-root': { borderRadius: 2, fontSize: '0.875rem' } }}
                        />
                    </Stack>
                </CardContent>
            </Card>

            {/* Notification list */}
            <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
                {loading ? (
                    <Box sx={{ display: 'flex', justifyContent: 'center', py: 6 }}>
                        <CircularProgress />
                    </Box>
                ) : filtered.length === 0 ? (
                    <Box sx={{ py: 8, textAlign: 'center' }}>
                        <Box sx={{
                            width: 72, height: 72, borderRadius: 4, mx: 'auto', mb: 2,
                            background: 'linear-gradient(135deg,#8E43F0,#0099C2)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                        }}>
                            <NotificationsOutlined sx={{ fontSize: 36, color: '#fff' }} />
                        </Box>
                        <Typography variant="h6" fontWeight={700} sx={{ mb: 0.5 }}>All caught up!</Typography>
                        <Typography variant="body2" color="text.secondary">No notifications match your filters</Typography>
                    </Box>
                ) : (
                    filtered.map((n, idx) => {
                        const isUnread = !n.read_at;
                        const meta = TYPE_COLORS[n.type] ?? TYPE_COLORS.info;
                        return (
                            <Box key={n.id}>
                                <Box
                                    sx={{
                                        display: 'flex', alignItems: 'flex-start', gap: 2, px: 3, py: 2,
                                        borderLeft: isUnread ? '3px solid' : '3px solid transparent',
                                        borderColor: isUnread ? 'primary.main' : 'transparent',
                                        bgcolor: isUnread ? 'rgba(142,67,240,0.03)' : 'transparent',
                                        transition: 'all 0.2s',
                                        '&:hover': { bgcolor: isUnread ? 'rgba(142,67,240,0.07)' : 'action.hover' },
                                        animation: `fadeIn 0.35s ease ${idx * 0.04}s both`,
                                        '@keyframes fadeIn': { from: { opacity: 0, transform: 'translateX(-6px)' }, to: { opacity: 1, transform: 'translateX(0)' } },
                                    }}
                                >
                                    {/* Colored dot with timeline line below */}
                                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', flexShrink: 0, mt: 0.5 }}>
                                        <Box sx={{ width: 10, height: 10, borderRadius: '50%', bgcolor: meta.text, boxShadow: `0 0 0 3px ${meta.bg}` }} />
                                        {idx < filtered.length - 1 && (
                                            <Box sx={{ width: 1.5, flex: 1, minHeight: 28, bgcolor: '#EDE8FC', mt: 0.5 }} />
                                        )}
                                    </Box>

                                    {/* Content */}
                                    <Box sx={{ flex: 1, minWidth: 0, pb: idx < filtered.length - 1 ? 1 : 0 }}>
                                        <Typography variant="body2" fontWeight={isUnread ? 700 : 400} sx={{ mb: 0.3 }}>
                                            {n.message}
                                        </Typography>
                                        <Stack direction="row" spacing={1.5} alignItems="center">
                                            <Chip
                                                label={meta.label}
                                                size="small"
                                                sx={{ height: 18, fontSize: '0.68rem', fontWeight: 700, bgcolor: meta.bg, color: meta.text, px: 0.5 }}
                                            />
                                            <Typography variant="caption" color="text.secondary">{timeAgo(n.created_at)}</Typography>
                                            <Typography variant="caption" color="text.disabled">{fmtDate(n.created_at)}</Typography>
                                        </Stack>
                                    </Box>

                                    {/* Actions */}
                                    <Stack direction="row" alignItems="center" spacing={1}>
                                        {isUnread && (
                                            <Tooltip title="Mark as read">
                                                <IconButton
                                                    size="small"
                                                    onClick={() => markRead({ variables: { id: n.id } })}
                                                    sx={{ color: 'primary.main', transition: 'all 0.15s', '&:hover': { bgcolor: '#EDE8FC', transform: 'scale(1.12)' } }}
                                                >
                                                    <DoneAll fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                        )}
                                        {isUnread && (
                                            <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: 'primary.main', flexShrink: 0, boxShadow: '0 0 0 2px #EDE8FC' }} />
                                        )}
                                    </Stack>
                                </Box>
                                {/* No Divider — timeline line handles separation */}
                            </Box>
                        );
                    })
                )}
            </Card>
        </Box>
    );
}
