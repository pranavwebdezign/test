import { useState, useMemo } from 'react';
import {
  Box, Grid, Card, CardContent, Typography, Stack, LinearProgress, Skeleton, Alert,
  Button, ButtonGroup, TextField, FormControl, InputLabel, Select, MenuItem,
  Divider, ToggleButtonGroup, ToggleButton, Chip, Tooltip,
} from '@mui/material';
import {
  BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid,
  Tooltip as ReTooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend,
} from 'recharts';
import { useQuery } from '@apollo/client';
import {
  FolderOpen, People, Assignment, CheckCircle, BarChart as BarChartIcon,
  ShowChart, FileDownload, PictureAsPdf, Print, FilterList,
} from '@mui/icons-material';
import PageHeader from '../../components/saas/PageHeader';
import StatsCard from '../../components/saas/StatsCard';
import StatusBadge from '../../components/saas/StatusBadge';
import { GET_DASHBOARD_STATS, GET_PROJECTS, GET_TASKS, GET_CLIENTS } from '../../graphql/queries';

const PIE_COLORS = { active: '#8E43F0', review: '#D97706', completed: '#16A34A', paused: '#9B89C4', planning: '#8E43F0' };
const PRIORITY_COLORS = { Critical: '#DC2626', High: '#D97706', Medium: '#8E43F0', Low: '#9B89C4' };
const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];

function presetRange(key) {
  const now = new Date();
  const from = new Date(now);
  if (key === '1m') from.setMonth(now.getMonth() - 1);
  else if (key === '3m') from.setMonth(now.getMonth() - 3);
  else if (key === '6m') from.setMonth(now.getMonth() - 6);
  else if (key === '1y') from.setFullYear(now.getFullYear() - 1);
  else return { from: '', to: '' };
  return { from: from.toISOString().slice(0, 10), to: now.toISOString().slice(0, 10) };
}

function toMonthLabel(iso) {
  if (!iso) return '?';
  const d = new Date(iso);
  return d.toLocaleDateString('en-GB', { month: 'short', year: '2-digit' });
}

const PRESETS = [
  { key: 'all', label: 'All time' },
  { key: '1m', label: '1 month' },
  { key: '3m', label: '3 months' },
  { key: '6m', label: '6 months' },
  { key: '1y', label: '1 year' },
];

// CSV export helper
function exportCSV(rows, filename) {
  if (!rows.length) return;
  const keys = Object.keys(rows[0]);
  const csv = [keys.join(','), ...rows.map((r) => keys.map((k) => JSON.stringify(r[k] ?? '')).join(','))].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: filename });
  a.click();
}

// Custom tooltip for recharts
const CustomTooltip = ({ active, payload, label }) => {
  if (!active || !payload?.length) return null;
  return (
    <Box sx={{ bgcolor: '#fff', border: '1px solid #DDD4F8', borderRadius: 2, p: 1.5, boxShadow: '0 4px 12px rgba(142,67,240,0.12)' }}>
      <Typography variant="caption" fontWeight={700} color="text.secondary">{label}</Typography>
      {payload.map((p, i) => (
        <Typography key={i} variant="caption" sx={{ display: 'block', color: p.color, fontWeight: 600 }}>
          {p.name}: {p.value}
        </Typography>
      ))}
    </Box>
  );
};

export default function Reports() {
  const [preset, setPreset] = useState('all');
  const [customFrom, setCustomFrom] = useState('');
  const [customTo, setCustomTo] = useState('');
  const [projFilter, setProjFilter] = useState('');
  const [chartType, setChartType] = useState('bar');   // 'bar' | 'line'

  const { data: statsData, loading: loadingStats } = useQuery(GET_DASHBOARD_STATS, { fetchPolicy: 'cache-and-network' });
  const { data: projectsData, loading: loadingProj } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-and-network' });
  const { data: tasksData, loading: loadingTasks } = useQuery(GET_TASKS, { fetchPolicy: 'cache-and-network' });
  const { data: clientsData, loading: loadingClients } = useQuery(GET_CLIENTS, { fetchPolicy: 'cache-and-network' });

  const stats = statsData?.dashboardStats ?? {};
  const allProjects = projectsData?.projects ?? [];
  const allTasks = tasksData?.tasks ?? [];
  const allClients = clientsData?.clients ?? [];

  const loading = loadingStats || loadingProj || loadingTasks || loadingClients;

  // ── Date bounds ─────────────────────────────────────────────
  const { from, to } = useMemo(() => {
    if (preset === 'custom') return { from: customFrom, to: customTo };
    return presetRange(preset);
  }, [preset, customFrom, customTo]);

  const inRange = (dateStr) => {
    if (!from && !to) return true;
    const d = new Date(dateStr);
    if (isNaN(d)) return false;
    if (from && d < new Date(from)) return false;
    if (to && d > new Date(to + 'T23:59:59')) return false;
    return true;
  };

  // ── Filtered data ────────────────────────────────────────────
  const projects = allProjects
    .filter((p) => !p.due_date || inRange(p.due_date))
    .filter((p) => !projFilter || p.id === projFilter);

  const tasks = allTasks.filter((t) => !t.due_date || inRange(t.due_date));
  const clients = allClients.filter((c) => !c.joined_at || inRange(c.joined_at));

  // ── KPI derived values ───────────────────────────────────────
  const totalRevenue = clients.reduce((s, c) => s + (parseFloat(c.revenue) || 0), 0);
  const completedProj = projects.filter((p) => p.status === 'completed').length;
  const completionRate = projects.length ? Math.round((completedProj / projects.length) * 100) : 0;
  const doneTasks = tasks.filter((t) => t.status === 'done').length;

  const statCards = [
    { title: 'Total Revenue', value: `£${(totalRevenue / 1000).toFixed(0)}k`, icon: <FolderOpen />, color: 'primary', trend: 18 },
    { title: 'Active Clients', value: from ? clients.length : (stats.active_clients ?? '…'), icon: <People />, color: 'success', trend: 8 },
    { title: 'Completion Rate', value: `${completionRate}%`, icon: <CheckCircle />, color: 'info', trend: 5 },
    { title: 'Tasks Done', value: from ? doneTasks : (stats.done_tasks ?? '…'), icon: <Assignment />, color: 'warning', trend: 12 },
  ];

  // ── Project status pie ────────────────────────────────────────
  const statusBreakdown = Object.entries(
    projects.reduce((acc, p) => { acc[p.status] = (acc[p.status] ?? 0) + 1; return acc; }, {})
  ).map(([name, value]) => ({ name, value }));

  // ── Task priority bars ────────────────────────────────────────
  const priorityBreakdown = PRIORITIES.map((p) => ({
    priority: p,
    count: tasks.filter((t) => t.priority?.toLowerCase() === p.toLowerCase()).length,
  }));
  const totalTasks = tasks.length || 1;

  // ── Client growth ─────────────────────────────────────────────
  const monthBuckets = {};
  clients.forEach((c) => {
    const mo = toMonthLabel(c.created_at ?? c.joined_at ?? null);
    if (!monthBuckets[mo]) monthBuckets[mo] = { month: mo, clients: 0, developers: 0 };
    monthBuckets[mo].clients += 1;
  });
  const growthData = Object.values(monthBuckets).slice(-7);
  const fallbackMonths = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
  const userGrowthData = growthData.length >= 2 ? growthData : fallbackMonths.map((month) => ({
    month,
    clients: Math.max(0, (stats.total_clients ?? 0) - Math.floor(Math.random() * 3)),
    developers: Math.max(0, (stats.active_projects ?? 0) - Math.floor(Math.random() * 2)),
  }));

  // ── Export helpers ────────────────────────────────────────────
  const handleExportCSV = () => exportCSV(projects.map((p) => ({ name: p.name, status: p.status, budget: p.budget, due_date: p.due_date })), 'projects-report.csv');
  const handleExportPDF = () => window.print();
  const handlePrint = () => window.print();

  const ChartSkeleton = () => <Skeleton variant="rounded" height={220} sx={{ mt: 2, borderRadius: 2 }} />;

  const chartCfg = { margin: { top: 4, right: 8, left: -20, bottom: 0 } };

  return (
    <Box>
      <PageHeader
        title="Reports"
        subtitle="Summary and analytics of your agency performance"
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Reports', path: '/reports' }]}
        action={
          /* Export ButtonGroup */
          <Stack direction="row" spacing={1}>
            <ButtonGroup size="small" variant="outlined" sx={{ '& .MuiButton-root': { borderRadius: 0, borderColor: '#DDD4F8', color: '#8E43F0' } }}>
              <Tooltip title="Export CSV">
                <Button startIcon={<FileDownload />} onClick={handleExportCSV}>CSV</Button>
              </Tooltip>
              <Tooltip title="Export PDF">
                <Button startIcon={<PictureAsPdf />} onClick={handleExportPDF}>PDF</Button>
              </Tooltip>
              <Tooltip title="Print">
                <Button startIcon={<Print />} onClick={handlePrint}>Print</Button>
              </Tooltip>
            </ButtonGroup>
          </Stack>
        }
      />

      {/* ── Filter Bar ──────────────────────────────────────────── */}
      <Card sx={{ mb: 3, borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', background: 'linear-gradient(135deg,#FDFCFF,#F5F0FF)' }}>
        <CardContent sx={{ p: 2.5 }}>
          <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 2 }}>
            <FilterList fontSize="small" sx={{ color: '#8E43F0' }} />
            <Typography variant="body2" fontWeight={700} color="primary.main">Filters</Typography>
          </Stack>

          <Stack direction={{ xs: 'column', sm: 'row' }} flexWrap="wrap" gap={2} alignItems="flex-end">
            {/* Date preset chips */}
            <Box>
              <Typography variant="caption" fontWeight={600} color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Date Range</Typography>
              <ButtonGroup size="small" variant="outlined" sx={{ flexWrap: 'wrap', gap: 0.5 }}>
                {PRESETS.map((p) => (
                  <Button
                    key={p.key}
                    variant={preset === p.key ? 'contained' : 'outlined'}
                    onClick={() => setPreset(p.key)}
                    sx={{ borderRadius: 1.5, fontWeight: preset === p.key ? 700 : 400 }}
                  >
                    {p.label}
                  </Button>
                ))}
                <Button variant={preset === 'custom' ? 'contained' : 'outlined'} onClick={() => setPreset('custom')} sx={{ borderRadius: 1.5 }}>
                  Custom
                </Button>
              </ButtonGroup>
            </Box>

            {/* Custom date fields */}
            {preset === 'custom' && (
              <Stack direction="row" spacing={1} alignItems="center">
                <TextField type="date" size="small" label="From" InputLabelProps={{ shrink: true }}
                  value={customFrom} onChange={(e) => setCustomFrom(e.target.value)} sx={{ width: 148, '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                <Typography variant="body2" color="text.secondary">–</Typography>
                <TextField type="date" size="small" label="To" InputLabelProps={{ shrink: true }}
                  value={customTo} onChange={(e) => setCustomTo(e.target.value)} sx={{ width: 148, '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
              </Stack>
            )}

            {/* Project filter */}
            <FormControl size="small" sx={{ minWidth: 180 }}>
              <InputLabel>Project</InputLabel>
              <Select value={projFilter} label="Project" onChange={(e) => setProjFilter(e.target.value)} sx={{ borderRadius: 2 }}>
                <MenuItem value=""><em>All Projects</em></MenuItem>
                {allProjects.map((p) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
              </Select>
            </FormControl>

            {/* Active filter summary chips */}
            {from && (
              <Stack direction="row" spacing={0.5} flexWrap="wrap">
                <Chip size="small" label={`${projects.length} projects`} sx={{ bgcolor: '#EDE8FC', color: '#8E43F0', fontWeight: 600 }} />
                <Chip size="small" label={`${tasks.length} tasks`} sx={{ bgcolor: '#FFF1F2', color: '#DC2626', fontWeight: 600 }} />
                <Chip size="small" label={`${clients.length} clients`} sx={{ bgcolor: '#F0FDF4', color: '#16A34A', fontWeight: 600 }} />
              </Stack>
            )}
          </Stack>
        </CardContent>
      </Card>

      {/* ── KPI Stats Row ─────────────────────────────────────── */}
      <Grid container spacing={2.5} sx={{ mb: 3 }}>
        {statCards.map((s) => (
          <Grid item xs={12} sm={6} lg={3} key={s.title}>
            {loadingStats
              ? <Skeleton variant="rounded" height={110} sx={{ borderRadius: 3 }} />
              : <StatsCard {...s} />
            }
          </Grid>
        ))}
      </Grid>

      {/* ── Charts Row ───────────────────────────────────────── */}
      <Grid container spacing={2.5} sx={{ mb: 3 }}>
        {/* Client Activity — with Bar/Line toggle */}
        <Grid item xs={12} lg={7}>
          <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
            <CardContent sx={{ p: 3 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="flex-start" flexWrap="wrap" gap={1} sx={{ mb: 1 }}>
                <Box>
                  <Typography variant="h6" fontWeight={700}>Client Activity</Typography>
                  <Typography variant="caption" color="text.secondary">Clients grouped by join month</Typography>
                </Box>
                {/* Chart type toggle */}
                <ToggleButtonGroup
                  value={chartType} exclusive onChange={(_, v) => v && setChartType(v)}
                  size="small" sx={{ '& .MuiToggleButton-root': { px: 1.5, py: 0.5, borderRadius: 1.5, border: '1px solid #DDD4F8', color: '#9B89C4', '&.Mui-selected': { bgcolor: '#EDE8FC', color: '#8E43F0' } } }}
                >
                  <ToggleButton value="bar" aria-label="bar chart">
                    <Tooltip title="Bar chart"><BarChartIcon fontSize="small" /></Tooltip>
                  </ToggleButton>
                  <ToggleButton value="line" aria-label="line chart">
                    <Tooltip title="Line chart"><ShowChart fontSize="small" /></Tooltip>
                  </ToggleButton>
                </ToggleButtonGroup>
              </Stack>

              {loading ? <ChartSkeleton /> : (
                <ResponsiveContainer width="100%" height={220} style={{ marginTop: 12 }}>
                  {chartType === 'bar' ? (
                    <BarChart data={userGrowthData} {...chartCfg} barGap={4}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#EDE8FC" vertical={false} />
                      <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                      <YAxis tick={{ fontSize: 12, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                      <ReTooltip content={<CustomTooltip />} />
                      <Legend wrapperStyle={{ fontSize: 12 }} />
                      <Bar dataKey="clients" name="Clients" fill="#8E43F0" radius={[4, 4, 0, 0]} />
                      <Bar dataKey="developers" name="Developers" fill="#0099C2" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  ) : (
                    <LineChart data={userGrowthData} {...chartCfg}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#EDE8FC" vertical={false} />
                      <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                      <YAxis tick={{ fontSize: 12, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                      <ReTooltip content={<CustomTooltip />} />
                      <Legend wrapperStyle={{ fontSize: 12 }} />
                      <Line type="monotone" dataKey="clients" name="Clients" stroke="#8E43F0" strokeWidth={2.5} dot={{ r: 4, fill: '#8E43F0' }} activeDot={{ r: 6 }} />
                      <Line type="monotone" dataKey="developers" name="Developers" stroke="#0099C2" strokeWidth={2.5} dot={{ r: 4, fill: '#0099C2' }} activeDot={{ r: 6 }} />
                    </LineChart>
                  )}
                </ResponsiveContainer>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* Project Status Pie */}
        <Grid item xs={12} lg={5}>
          <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
            <CardContent sx={{ p: 3 }}>
              <Typography variant="h6" fontWeight={700}>Project Status</Typography>
              <Typography variant="caption" color="text.secondary">{projects.length} projects · breakdown by status</Typography>
              {loading ? <ChartSkeleton /> : (
                <ResponsiveContainer width="100%" height={220} style={{ marginTop: 8 }}>
                  <PieChart>
                    <Pie data={statusBreakdown} cx="50%" cy="50%" outerRadius={80}
                      dataKey="value" nameKey="name"
                      label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                      labelLine={false}>
                      {statusBreakdown.map((entry) => (
                        <Cell key={entry.name} fill={PIE_COLORS[entry.name] ?? '#DDD4F8'} />
                      ))}
                    </Pie>
                    <ReTooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ fontSize: 12 }} />
                  </PieChart>
                </ResponsiveContainer>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* ── Task Priority Breakdown ───────────────────────────── */}
      <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
        <CardContent sx={{ p: 3 }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 2 }}>
            <Box>
              <Typography variant="h6" fontWeight={700}>
                Task Priority Breakdown
              </Typography>
              <Typography variant="caption" color="text.secondary">
                {tasks.length} {from ? 'filtered' : 'total'} tasks
              </Typography>
            </Box>
            <Chip
              label={`${doneTasks} / ${tasks.length} done`}
              size="small"
              sx={{ bgcolor: '#F0FDF4', color: '#16A34A', fontWeight: 700 }}
            />
          </Stack>
          <Divider sx={{ mb: 2, borderColor: '#EDE8FC' }} />
          {loading
            ? [1, 2, 3, 4].map((i) => <Skeleton key={i} height={40} sx={{ mb: 1, borderRadius: 2 }} />)
            : (
              <Stack spacing={2}>
                {priorityBreakdown.map((row) => (
                  <Box key={row.priority} sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                    <StatusBadge value={row.priority} />
                    <LinearProgress
                      variant="determinate"
                      value={(row.count / totalTasks) * 100}
                      sx={{
                        flex: 1, borderRadius: 4, height: 8, bgcolor: '#EDE8FC',
                        '& .MuiLinearProgress-bar': { bgcolor: PRIORITY_COLORS[row.priority] ?? '#9B89C4' }
                      }}
                    />
                    <Typography variant="body2" fontWeight={700} sx={{ minWidth: 30, textAlign: 'right' }}>
                      {row.count}
                    </Typography>
                  </Box>
                ))}
              </Stack>
            )
          }
        </CardContent>
      </Card>
    </Box>
  );
}
