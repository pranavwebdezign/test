import { useState } from 'react';
import {
  Box, Typography, Button, Chip, Grid, Paper,
  Card, CardContent, Tooltip, Select, MenuItem,
  Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Avatar, Stack,
} from '@mui/material';
import {
  Public, CheckCircle, Warning, Cancel, List as ListIcon,
  Refresh, Add, Download, AccessTime, Link as LinkIcon,
} from '@mui/icons-material';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip as RechartsTooltip } from 'recharts';
import { formatDistanceToNow } from 'date-fns';
import { useQuery } from '@apollo/client';
import AppLayout from '../components/layout/AppLayout';
import { GET_WP_DASHBOARD, GET_WP_SITES, GET_WP_WORK_ITEMS } from '../graphql/queries';

const severityColors = { P0: 'error', P1: 'warning', P2: 'info', P3: 'default' };

function StatCard({ title, value, icon, color }) {
  return (
    <Card variant="outlined" sx={{ height: '100%' }}>
      <CardContent>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
          {icon}
          <Typography variant="body2" color="text.secondary">{title}</Typography>
        </Box>
        <Typography variant="h3" fontWeight={700} sx={{ color }}>{value}</Typography>
      </CardContent>
    </Card>
  );
}

const PIE_COLORS = { healthy: '#16A34A', warning: '#D97706', critical: '#DC2626', unknown: '#9CA3AF' };

function WpHealthPie({ sites }) {
  const counts = sites.reduce((acc, s) => {
    const h = s.overall_health ?? 'unknown';
    acc[h] = (acc[h] ?? 0) + 1;
    return acc;
  }, {});

  const pieData = [
    { name: 'Healthy', value: counts.healthy ?? 0, color: PIE_COLORS.healthy },
    { name: 'Warning', value: counts.warning ?? 0, color: PIE_COLORS.warning },
    { name: 'Critical', value: counts.critical ?? 0, color: PIE_COLORS.critical },
    { name: 'Unknown', value: counts.unknown ?? 0, color: PIE_COLORS.unknown },
  ].filter((d) => d.value > 0);

  if (!sites.length) return null;

  return (
    <Paper variant="outlined" sx={{ p: 2, mb: 3 }}>
      <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
        Site Health Distribution
        <Typography component="span" variant="caption" color="text.secondary" sx={{ ml: 1 }}>
          {sites.length} total sites
        </Typography>
      </Typography>
      <Grid container spacing={2} alignItems="center">
        <Grid item xs={12} sm={5} md={4}>
          <ResponsiveContainer width="100%" height={160}>
            <PieChart>
              <Pie
                data={pieData}
                cx="50%"
                cy="50%"
                innerRadius={45}
                outerRadius={70}
                paddingAngle={2}
                dataKey="value"
              >
                {pieData.map((entry) => (
                  <Cell key={entry.name} fill={entry.color} />
                ))}
              </Pie>
              <RechartsTooltip formatter={(v, name) => [`${v} sites`, name]} />
            </PieChart>
          </ResponsiveContainer>
        </Grid>
        <Grid item xs={12} sm={7} md={8}>
          <Stack direction="row" flexWrap="wrap" gap={2}>
            {pieData.map((d) => (
              <Box key={d.name} sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                <Box sx={{ width: 12, height: 12, borderRadius: '50%', bgcolor: d.color, flexShrink: 0 }} />
                <Typography variant="body2" fontWeight={600}>{d.value}</Typography>
                <Typography variant="body2" color="text.secondary">{d.name}</Typography>
              </Box>
            ))}
          </Stack>
        </Grid>
      </Grid>
    </Paper>
  );
}

export default function Dashboard() {
  const { data: dashData } = useQuery(GET_WP_DASHBOARD, { fetchPolicy: 'cache-first' });
  const { data: sitesData } = useQuery(GET_WP_SITES, { fetchPolicy: 'cache-first' });
  const { data: workItemsData } = useQuery(GET_WP_WORK_ITEMS, {
    variables: { status: 'open' },
    fetchPolicy: 'cache-first'
  });

  const stats = dashData?.wpDashboard || {
    total_sites: 0,
    healthy_sites: 0,
    warning_sites: 0,
    critical_sites: 0,
    open_work_items: 0
  };

  const sites = sitesData?.wpSites || [];
  const sitesNeedingAttention = sites.filter((s) => s.overall_health !== 'healthy');
  
  const openWorkItems = workItemsData?.wpWorkItems || [];

  const handleStatusChange = (id, status) => {
    // API Mutation needed here in real implementation
  };

  const healthColor = { healthy: 'success.main', warning: 'warning.main', critical: 'error.main', unknown: 'text.disabled' };
  const healthChipColor = { healthy: 'success', warning: 'warning', critical: 'error', unknown: 'default' };

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 }, pt: { xs: 7, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Dashboard</Typography>
            <Typography variant="body2" color="text.secondary">Monitor your WordPress sites at a glance</Typography>
          </Box>
          <Stack direction="row" spacing={1} flexWrap="wrap">
            <Button variant="outlined" startIcon={<Download />}>Export</Button>
            <Button variant="outlined" startIcon={<Refresh />}>Refresh All</Button>
            <Button variant="contained" startIcon={<Add />}>Add Site</Button>
          </Stack>
        </Box>

        {/* Stat Cards */}
        <Grid container spacing={2} sx={{ mb: 3 }}>
          <Grid item xs={12} sm={6} lg={2.4}>
            <StatCard title="Total Sites" value={stats.total_sites} icon={<Public color="primary" />} color="primary.main" />
          </Grid>
          <Grid item xs={12} sm={6} lg={2.4}>
            <StatCard title="Healthy" value={stats.healthy_sites} icon={<CheckCircle color="success" />} color="success.main" />
          </Grid>
          <Grid item xs={12} sm={6} lg={2.4}>
            <StatCard title="Warnings" value={stats.warning_sites} icon={<Warning color="warning" />} color="warning.main" />
          </Grid>
          <Grid item xs={12} sm={6} lg={2.4}>
            <StatCard title="Critical" value={stats.critical_sites} icon={<Cancel color="error" />} color="error.main" />
          </Grid>
          <Grid item xs={12} sm={6} lg={2.4}>
            <StatCard title="Open Tasks" value={stats.open_work_items} icon={<ListIcon color="primary" />} color="primary.main" />
          </Grid>
        </Grid>

        {/* WP Health Distribution */}
        <WpHealthPie sites={sites} />

        <Grid container spacing={3}>
          {/* Needs Attention */}
          <Grid item xs={12} lg={4}>
            <Paper variant="outlined" sx={{ height: '100%' }}>
              <Box sx={{ px: 2, py: 1.5, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', justifyContent: 'space-between' }}>
                <Typography variant="subtitle1" fontWeight={600}>Needs Attention</Typography>
                <Typography variant="body2" color="text.secondary">{sitesNeedingAttention.length} sites</Typography>
              </Box>
              {sitesNeedingAttention.length > 0 ? (
                sitesNeedingAttention.map((site) => {
                  const totalUpdates =
                    (site.plugin_updates || 0) + (site.theme_updates || 0) + (site.core_update_available ? 1 : 0);
                  return (
                    <Box
                      key={site.id}
                      sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider', '&:last-child': { borderBottom: 'none' } }}
                    >
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                        <Box>
                          <Typography variant="body2" fontWeight={600}>{site.name}</Typography>
                          <Typography variant="caption" color="text.secondary">{site.wp_client?.name || 'Unassigned'}</Typography>
                        </Box>
                        <Chip
                          label={site.overall_health.charAt(0).toUpperCase() + site.overall_health.slice(1)}
                          color={healthChipColor[site.overall_health]}
                          size="small"
                        />
                      </Box>
                      <Stack direction="row" spacing={1} flexWrap="wrap">
                        <Chip label={`PHP ${site.php_version || '—'}`} size="small" variant="outlined" />
                        <Chip label={`WP ${site.wp_version || '—'}`} size="small" variant="outlined" />
                        {totalUpdates > 0 && (
                          <Chip label={`${totalUpdates} update${totalUpdates !== 1 ? 's' : ''}`} size="small" color="warning" />
                        )}
                      </Stack>
                      <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'flex', alignItems: 'center', gap: 0.5 }}>
                        <AccessTime sx={{ fontSize: 12 }} />
                        {site.last_checked_at
                          ? formatDistanceToNow(new Date(site.last_checked_at), { addSuffix: true })
                          : 'Never checked'}
                      </Typography>
                    </Box>
                  );
                })
              ) : (
                <Box sx={{ p: 6, textAlign: 'center' }}>
                  <CheckCircle sx={{ fontSize: 48, color: 'success.main', mb: 2 }} />
                  <Typography variant="body2" color="text.secondary">All sites are healthy!</Typography>
                </Box>
              )}
            </Paper>
          </Grid>

          {/* Work Queue */}
          <Grid item xs={12} lg={8}>
            <Paper variant="outlined">
              <Box sx={{ px: 2, py: 1.5, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', justifyContent: 'space-between' }}>
                <Typography variant="subtitle1" fontWeight={600}>Work Queue</Typography>
                <Typography variant="body2" color="text.secondary">{openWorkItems.length} active items</Typography>
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
                    {openWorkItems.map((row) => (
                      <TableRow key={row.id} hover>
                        <TableCell>
                          <Chip label={row.severity} color={severityColors[row.severity]} size="small" sx={{ fontWeight: 600 }} />
                        </TableCell>
                        <TableCell>
                          <Typography variant="body2" fontWeight={600}>{row.title}</Typography>
                          <Typography variant="caption" color="text.secondary">
                            {row.site?.name || 'Unknown Site'} • {row.site?.wp_client?.name || 'No Client'}
                          </Typography>
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
                            sx={{ width: 140, fontSize: 13 }}
                          >
                            <MenuItem value="open">Open</MenuItem>
                            <MenuItem value="acknowledged">Acknowledged</MenuItem>
                            <MenuItem value="in_progress">In Progress</MenuItem>
                            <MenuItem value="done">Done</MenuItem>
                            <MenuItem value="not_applicable">N/A</MenuItem>
                          </Select>
                        </TableCell>
                        <TableCell>
                          {row.assignee ? (
                            <Tooltip title={row.assignee.name}>
                              <Avatar sx={{ width: 28, height: 28, fontSize: 11, bgcolor: 'primary.main', src: row.assignee.avatar_url }}>
                                {!row.assignee.avatar_url && row.assignee.name.split(' ').map((n) => n[0]).join('')}
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
            </Paper>
          </Grid>
        </Grid>
      </Box>
    </AppLayout>
  );
}
