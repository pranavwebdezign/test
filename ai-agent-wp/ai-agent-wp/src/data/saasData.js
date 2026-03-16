// ─── Mock data for the SaaS dashboard ───────────────────────────────────────

export const mockProjects = [
  { id: 'p1', name: 'E-Commerce Redesign',   client: 'Acme Corp',        developer: 'Sam Carter',   progress: 75, status: 'active',    budget: 15000, due: '2026-04-15' },
  { id: 'p2', name: 'CRM Integration',        client: 'TechFlow Inc',     developer: 'Lee Wong',     progress: 40, status: 'active',    budget: 9500,  due: '2026-05-01' },
  { id: 'p3', name: 'Mobile App MVP',         client: 'StartUp X',        developer: 'Sam Carter',   progress: 90, status: 'review',    budget: 22000, due: '2026-03-20' },
  { id: 'p4', name: 'Brand Portal',           client: 'Luxe Brands',      developer: 'Maya Patel',   progress: 20, status: 'active',    budget: 7800,  due: '2026-06-30' },
  { id: 'p5', name: 'Analytics Dashboard',    client: 'DataViz Ltd',      developer: 'Kim Torres',   progress: 100,status: 'completed', budget: 12000, due: '2026-02-28' },
  { id: 'p6', name: 'API Gateway Setup',      client: 'Acme Corp',        developer: 'Lee Wong',     progress: 55, status: 'active',    budget: 6000,  due: '2026-04-10' },
  { id: 'p7', name: 'SEO Optimization',       client: 'GreenLeaf Co',     developer: 'Maya Patel',   progress: 30, status: 'active',    budget: 4500,  due: '2026-05-15' },
  { id: 'p8', name: 'Security Audit',         client: 'FinTrust Bank',    developer: 'Kim Torres',   progress: 65, status: 'active',    budget: 18000, due: '2026-04-01' },
];

export const mockClients = [
  { id: 'c1', name: 'Acme Corp',     email: 'contact@acme.com',      projects: 2, status: 'active',   joined: '2025-01-15', revenue: 21000 },
  { id: 'c2', name: 'TechFlow Inc',  email: 'hello@techflow.io',     projects: 1, status: 'active',   joined: '2025-03-02', revenue: 9500  },
  { id: 'c3', name: 'StartUp X',     email: 'team@startupx.co',     projects: 1, status: 'active',   joined: '2025-06-18', revenue: 22000 },
  { id: 'c4', name: 'Luxe Brands',   email: 'ops@luxebrands.com',    projects: 1, status: 'active',   joined: '2025-08-05', revenue: 7800  },
  { id: 'c5', name: 'DataViz Ltd',   email: 'info@dataviz.ai',       projects: 1, status: 'inactive', joined: '2024-11-20', revenue: 12000 },
  { id: 'c6', name: 'GreenLeaf Co',  email: 'grow@greenleaf.com',    projects: 1, status: 'active',   joined: '2025-09-10', revenue: 4500  },
  { id: 'c7', name: 'FinTrust Bank', email: 'digital@fintrust.bank', projects: 1, status: 'active',   joined: '2025-10-22', revenue: 18000 },
];

export const mockUsers = [
  { id: 'u1', name: 'Alex Johnson',  email: 'admin@demo.com',     role: 'SuperAdmin', status: 'active',   joined: '2024-09-01', company: 'Webdezign Ltd' },
  { id: 'u2', name: 'Sam Carter',    email: 'dev@demo.com',       role: 'Developer',  status: 'active',   joined: '2024-10-15', company: 'Webdezign Ltd' },
  { id: 'u3', name: 'Jordan Smith',  email: 'client@demo.com',    role: 'Client',     status: 'active',   joined: '2025-01-15', company: 'Acme Corp'     },
  { id: 'u4', name: 'Lee Wong',      email: 'lee@demo.com',       role: 'Developer',  status: 'active',   joined: '2025-02-08', company: 'Webdezign Ltd' },
  { id: 'u5', name: 'Maya Patel',    email: 'maya@demo.com',      role: 'Developer',  status: 'active',   joined: '2025-04-12', company: 'Webdezign Ltd' },
  { id: 'u6', name: 'Kim Torres',    email: 'kim@demo.com',       role: 'Developer',  status: 'inactive', joined: '2025-05-20', company: 'Webdezign Ltd' },
  { id: 'u7', name: 'Riley Brooks',  email: 'riley@demo.com',     role: 'Client',     status: 'active',   joined: '2025-07-30', company: 'StartUp X'     },
];

export const mockDevelopers = [
  { id: 'd1', name: 'Sam Carter',  email: 'dev@demo.com',    specialization: 'Full Stack',   activeProjects: 2, status: 'active',   skills: ['React','Node','AWS']      },
  { id: 'd2', name: 'Lee Wong',    email: 'lee@demo.com',    specialization: 'Backend',       activeProjects: 2, status: 'active',   skills: ['Python','Django','GCP']   },
  { id: 'd3', name: 'Maya Patel',  email: 'maya@demo.com',   specialization: 'Frontend',      activeProjects: 2, status: 'active',   skills: ['React','Vue','CSS']       },
  { id: 'd4', name: 'Kim Torres',  email: 'kim@demo.com',    specialization: 'DevOps',        activeProjects: 1, status: 'inactive', skills: ['Docker','K8s','Terraform'] },
];

export const mockTasks = [
  { id: 't1',  project: 'E-Commerce Redesign', title: 'Build product page component',   priority: 'High',   status: 'in_progress', assignee: 'Sam Carter',  due: '2026-03-18' },
  { id: 't2',  project: 'CRM Integration',      title: 'Connect Salesforce API',          priority: 'Critical',status: 'open',       assignee: 'Lee Wong',    due: '2026-03-20' },
  { id: 't3',  project: 'Mobile App MVP',        title: 'Implement push notifications',    priority: 'Medium', status: 'review',      assignee: 'Sam Carter',  due: '2026-03-15' },
  { id: 't4',  project: 'Brand Portal',          title: 'Design asset upload flow',        priority: 'Low',    status: 'open',        assignee: 'Maya Patel',  due: '2026-04-05' },
  { id: 't5',  project: 'Analytics Dashboard',   title: 'Final QA and sign off',          priority: 'High',   status: 'done',        assignee: 'Kim Torres',  due: '2026-02-25' },
  { id: 't6',  project: 'API Gateway Setup',     title: 'Write API documentation',        priority: 'Medium', status: 'in_progress', assignee: 'Lee Wong',    due: '2026-03-25' },
  { id: 't7',  project: 'SEO Optimization',      title: 'Audit page speed scores',        priority: 'Medium', status: 'open',        assignee: 'Maya Patel',  due: '2026-04-10' },
  { id: 't8',  project: 'Security Audit',        title: 'Penetration testing report',     priority: 'Critical',status: 'in_progress', assignee: 'Kim Torres',  due: '2026-03-30' },
  { id: 't9',  project: 'E-Commerce Redesign', title: 'Integrate payment gateway',       priority: 'High',   status: 'open',        assignee: 'Sam Carter',  due: '2026-04-01' },
  { id: 't10', project: 'CRM Integration',      title: 'Set up user sync cron job',       priority: 'Low',    status: 'done',        assignee: 'Lee Wong',    due: '2026-03-10' },
];

export const mockNotifications = [
  { id: 'n1', type: 'info',    message: 'Mobile App MVP is ready for review',            time: '5 min ago',  read: false },
  { id: 'n2', type: 'success', message: 'Security Audit — task updated by Kim Torres',   time: '1 hr ago',   read: false },
  { id: 'n3', type: 'warning', message: 'CRM Integration deadline is in 2 days',         time: '3 hr ago',   read: true  },
  { id: 'n4', type: 'info',    message: 'New client Jordan Smith signed up',              time: 'Yesterday',  read: true  },
];

export const projectActivityData = [
  { month: 'Sep', projects: 2, tasks: 8  },
  { month: 'Oct', projects: 3, tasks: 14 },
  { month: 'Nov', projects: 3, tasks: 12 },
  { month: 'Dec', projects: 4, tasks: 20 },
  { month: 'Jan', projects: 5, tasks: 18 },
  { month: 'Feb', projects: 6, tasks: 25 },
  { month: 'Mar', projects: 8, tasks: 30 },
];

export const userGrowthData = [
  { month: 'Sep', clients: 1, developers: 1 },
  { month: 'Oct', clients: 2, developers: 2 },
  { month: 'Nov', clients: 3, developers: 3 },
  { month: 'Dec', clients: 4, developers: 3 },
  { month: 'Jan', clients: 5, developers: 4 },
  { month: 'Feb', clients: 6, developers: 4 },
  { month: 'Mar', clients: 7, developers: 4 },
];

export const revenueData = [
  { month: 'Sep', revenue: 8500  },
  { month: 'Oct', revenue: 14200 },
  { month: 'Nov', revenue: 13800 },
  { month: 'Dec', revenue: 19500 },
  { month: 'Jan', revenue: 22000 },
  { month: 'Feb', revenue: 18500 },
  { month: 'Mar', revenue: 24800 },
];
