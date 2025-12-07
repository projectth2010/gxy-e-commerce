import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';

// UI Components
const Card = ({ children, className = '' }) => (
  <div className={`bg-white rounded-lg shadow p-6 ${className}`}>
    {children}
  </div>
);

const Metric = ({ children, className = '' }) => (
  <div className={`text-2xl font-semibold text-gray-900 ${className}`}>
    {children}
  </div>
);

const Text = ({ children, className = '' }) => (
  <p className={`text-sm text-gray-500 ${className}`}>
    {children}
  </p>
);

// Icons
const CurrencyDollarIcon = () => (
  <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
);

const UserGroupIcon = () => (
  <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
  </svg>
);

const ArrowUpIcon = () => (
  <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
    <path fillRule="evenodd" d="M12 7a1 1 0 01-1 1H9v1h2a1 1 0 110 2H9v1a1 1 0 11-2 0v-1H5a1 1 0 110-2h2V8a1 1 0 011-1h4z" clipRule="evenodd" />
  </svg>
);

const ArrowDownIcon = () => (
  <svg className="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
    <path fillRule="evenodd" d="M12 13a1 1 0 01-1 1H9v1a1 1 0 11-2 0v-1H5a1 1 0 110-2h2v-1a1 1 0 112 0v1h2a1 1 0 011 1z" clipRule="evenodd" />
  </svg>
);

export default function SubscriptionDashboard({ metrics = {}, auth }) {
    const [timeRange, setTimeRange] = useState('30d');
    const [chartData, setChartData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedMetric, setSelectedMetric] = useState('mrr');

    useEffect(() => {
        fetchChartData(selectedMetric, timeRange);
    }, [timeRange, selectedMetric]);

    const fetchChartData = async (metric, range) => {
        setLoading(true);
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 500));
            // In a real app, you would fetch data from your API
            // const response = await fetch(`/admin/subscriptions/metrics?metric=${metric}&period=${range}`);
            // const data = await response.json();
            // setChartData(data);
        } catch (error) {
            console.error('Error fetching chart data:', error);
        } finally {
            setLoading(false);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value || 0);
    };

    const formatPercentage = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
        }).format((value || 0) / 100);
    };

    const metricCards = [
        {
            title: 'Monthly Recurring Revenue',
            value: formatCurrency(metrics.mrr || 0),
            icon: <CurrencyDollarIcon />,
            change: metrics.mrr_growth_rate || 0,
            description: 'Total monthly revenue from active subscriptions',
        },
        {
            title: 'Active Subscriptions',
            value: (metrics.active_subscriptions || 0).toLocaleString(),
            icon: <UserGroupIcon />,
            change: metrics.subscription_growth_rate || 0,
            description: 'Total number of active subscriptions',
        },
        {
            title: 'Churn Rate',
            value: formatPercentage(metrics.churn_rate || 0),
            icon: <ArrowDownIcon />,
            change: -Math.abs(metrics.churn_rate || 0),
            description: 'Percentage of customers who canceled',
            isNegative: true,
        },
        {
            title: 'Avg. Revenue Per User',
            value: formatCurrency(metrics.average_revenue_per_user || 0),
            icon: <CurrencyDollarIcon />,
            change: metrics.arpa_growth_rate || 0,
            description: 'Average monthly revenue per user',
        },
    ];

    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="Subscription Dashboard" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <h1 className="text-2xl font-semibold text-gray-900">Subscription Dashboard</h1>
                </div>
                
                <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    {/* Time range selector */}
                    <div className="flex justify-end mb-6">
                        <select
                            value={timeRange}
                            onChange={(e) => setTimeRange(e.target.value)}
                            className="mt-1 block w-32 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        >
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                            <option value="90d">Last 90 days</option>
                            <option value="12m">Last 12 months</option>
                        </select>
                    </div>
                    
                    {/* Metric cards */}
                    <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
                        {metricCards.map((card, index) => (
                            <Card key={index} className="overflow-hidden">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        {card.icon}
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <Text>{card.title}</Text>
                                        <div className="flex items-baseline">
                                            <Metric>{card.value}</Metric>
                                            {card.change !== 0 && (
                                                <span className={`ml-2 flex items-center text-sm font-medium ${card.change >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                    {card.change >= 0 ? (
                                                        <ArrowUpIcon />
                                                    ) : (
                                                        <ArrowDownIcon />
                                                    )}
                                                    <span className="sr-only">
                                                        {card.change >= 0 ? 'Increased' : 'Decreased'} by
                                                    </span>
                                                    {Math.abs(card.change).toFixed(1)}%
                                                </span>
                                            )}
                                        </div>
                                        <Text className="mt-1">{card.description}</Text>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                    
                    {/* Charts section */}
                    <div className="mt-8">
                        <Card className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-lg font-medium text-gray-900">Subscription Metrics</h2>
                                <div className="flex space-x-2">
                                    {['mrr', 'subscribers', 'churn', 'arpa'].map((metric) => (
                                        <button
                                            key={metric}
                                            type="button"
                                            onClick={() => setSelectedMetric(metric)}
                                            className={`px-3 py-1 text-sm font-medium rounded-md ${
                                                selectedMetric === metric
                                                    ? 'bg-indigo-100 text-indigo-700'
                                                    : 'text-gray-500 hover:bg-gray-100'
                                            }`}
                                        >
                                            {metric.toUpperCase()}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            
                            <div className="h-80 flex items-center justify-center bg-gray-50 rounded-md">
                                {loading ? (
                                    <div className="text-gray-500">Loading chart data...</div>
                                ) : chartData.length > 0 ? (
                                    <div className="w-full h-full">
                                        {/* Chart would be rendered here */}
                                        <div className="flex items-center justify-center h-full text-gray-500">
                                            Chart for {selectedMetric} would be displayed here
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-gray-500">No data available for the selected period</div>
                                )}
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}
},
        {
            title: 'Active Subscriptions',
            value: (metrics.active_subscriptions || 0).toLocaleString(),
            change: metrics.subscription_growth_rate || 0,
            icon: UserGroupIcon,
            color: 'green',
            description: 'Total active subscriptions',
            trend: metrics.subscription_trend || 'up'
        },
        {
            title: 'Customer LTV',
            value: formatCurrency(metrics.customer_lifetime_value || 0),
            change: metrics.ltv_growth_rate || 0,
            icon: CurrencyEuroIcon,
            color: 'purple',
            description: 'Average lifetime value per customer',
            trend: metrics.ltv_trend || 'up'
        },
        {
            title: 'Retention Rate',
            value: formatPercentage(metrics.retention_rate || 0),
            change: (metrics.retention_rate || 0) - 95, // Compare to 95% baseline
            icon: ShieldCheckIcon,
            color: 'indigo',
            description: 'Customer retention over last period',
            trend: metrics.retention_trend || 'stable'
        },
        {
            title: 'Expansion MRR',
            value: formatCurrency(metrics.expansion_mrr || 0),
            change: metrics.expansion_rate || 0,
            icon: ArrowUpCircleIcon,
            color: 'emerald',
            description: 'Revenue from upgrades & add-ons',
            trend: metrics.expansion_trend || 'up'
        },
        {
            title: 'Churn Rate',
            value: formatPercentage(metrics.churn_rate || 0),
            change: -1 * (metrics.churn_rate || 0), // Invert for positive=good
            icon: ArrowTrendingDownIcon,
            color: 'rose',
            description: 'Customer churn this period',
            trend: metrics.churn_trend || 'down'
        },
        {
            title: 'ARPU',
            value: formatCurrency(metrics.average_revenue_per_user || 0),
            change: metrics.arpu_growth_rate || 0,
            icon: UserCircleIcon,
            color: 'sky',
            description: 'Average revenue per user',
            trend: metrics.arpu_trend || 'up'
        },
        {
            title: 'Trial Conversion',
            value: formatPercentage(metrics.trial_conversion_rate || 0),
            change: (metrics.trial_conversion_rate || 0) - 15, // Compare to 15% baseline
            icon: CheckCircleIcon,
            color: 'amber',
            description: 'Trial to paid conversion rate',
            trend: metrics.conversion_trend || 'stable'
        },
        {
            title: 'Trial Subscriptions',
            value: metrics.trial_subscriptions.toLocaleString(),
            change: null,
            icon: '🔍',
        },
        {
            title: 'Churn Rate',
            value: `${(metrics.churn_rate * 100).toFixed(2)}%`,
            change: null,
            icon: '📉',
            isNegative: true,
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Subscription Dashboard" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-semibold text-gray-900">Subscription Dashboard</h2>
                        <div className="flex space-x-2">
                            <select
                                value={timeRange}
                                onChange={(e) => setTimeRange(e.target.value)}
                                className="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="7d">Last 7 days</option>
                                <option value="30d">Last 30 days</option>
                                <option value="90d">Last 90 days</option>
                                <option value="12m">Last 12 months</option>
                            </select>
                            
                            <select
                                value={selectedMetric}
                                onChange={(e) => setSelectedMetric(e.target.value)}
                                className="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="mrr">MRR</option>
                                <option value="active_subscriptions">Active Subscriptions</option>
                                <option value="churn_rate">Churn Rate</option>
                            </select>
                        </div>
                    </div>

                    {/* Metrics Grid */}
                    <div className="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
                        {metricCards.map((card, index) => {
                            const isPositive = card.change >= 0;
                            const trendColor = isPositive ? 'green' : 'red';
                            const trendIcon = isPositive ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
                            
                            return (
                                <Card key={index} className="p-5 hover:shadow-md transition-shadow duration-200">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between">
                                                <Text className="text-sm font-medium text-gray-500">{card.title}</Text>
                                                {card.trend && (
                                                    <div className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-${card.color}-100 text-${card.color}-800`}>
                                                        {card.trend === 'up' ? '↑' : card.trend === 'down' ? '↓' : '→'} {card.trend}
                                                    </div>
                                                )}
                                            </div>
                                            <Metric className="mt-1 text-2xl font-semibold">{card.value}</Metric>
                                            
                                            {(card.change !== null && card.change !== undefined) && (
                                                <div className={`mt-2 flex items-center text-sm ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                                                    <Icon icon={trendIcon} className="h-4 w-4 mr-1" />
                                                    {Math.abs(card.change).toFixed(1)}% {isPositive ? 'increase' : 'decrease'}
                                                    <span className="ml-1 text-xs text-gray-500">vs last period</span>
                                                </div>
                                            )}
                                            
                                            {card.description && (
                                                <div className="mt-2 text-xs text-gray-500">
                                                    {card.description}
                                                </div>
                                            )}
                                        </div>
                                        
                                        <div className={`ml-4 p-3 rounded-lg bg-${card.color}-50`}>
                                            {typeof card.icon === 'string' ? (
                                                <span className="text-2xl">{card.icon}</span>
                                            ) : (
                                                <card.icon className={`h-6 w-6 text-${card.color}-600`} />
                                            )}
                                        </div>
                                    </div>
                                    
                                    {/* Mini trend line chart (placeholder) */}
                                    <div className="mt-4 h-12 w-full flex items-end">
                                        {[3, 7, 5, 8, 10, 5, 9].map((value, i) => (
                                            <div 
                                                key={i}
                                                className={`flex-1 mx-0.5 rounded-t-sm bg-${card.color}-200`}
                                                style={{ height: `${value * 5}%` }}
                                            />
                                        ))}
                                    </div>
                                </Card>
                            );
                        })}
                    </div>

                    {/* Charts */}
                    <div className="grid gap-6 mb-8 md:grid-cols-2">
                        <Card className="p-6">
                            <Title>Revenue by Plan</Title>
                            <DonutChart
                                className="mt-6"
                                data={Object.entries(metrics.revenue_by_plan || {}).map(([name, value]) => ({
                                    name,
                                    value,
                                }))}
                                category="value"
                                index="name"
                                colors={['blue', 'violet', 'indigo', 'rose', 'cyan']}
                            />
                            <Legend
                                className="mt-4"
                                categories={Object.keys(metrics.revenue_by_plan || {})}
                                colors={['blue', 'violet', 'indigo', 'rose', 'cyan']}
                            />
                        </Card>

                        <Card className="p-6">
                            <Title>Subscription Health</Title>
                            <div className="mt-6">
                                <div className="flex items-center justify-between mb-2">
                                    <Text>Overall Health Score</Text>
                                    <Text className="text-lg font-semibold">{metrics.subscription_health}/100</Text>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-4">
                                    <div 
                                        className={`h-4 rounded-full ${metrics.subscription_health > 70 ? 'bg-green-500' : metrics.subscription_health > 40 ? 'bg-yellow-500' : 'bg-red-500'}`}
                                        style={{ width: `${metrics.subscription_health}%` }}
                                    ></div>
                                </div>
                                <div className="mt-4 space-y-2">
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-500">Critical</span>
                                        <span className="text-sm text-gray-900">
                                            {metrics.recent_alerts?.filter(a => a.level === 'critical').length || 0}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-500">Warnings</span>
                                        <span className="text-sm text-gray-900">
                                            {metrics.recent_alerts?.filter(a => a.level === 'warning').length || 0}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    {/* Recent Alerts */}
                    <Card className="p-6">
                        <Title>Recent Alerts</Title>
                        <div className="mt-6 space-y-4">
                            {metrics.recent_alerts?.length > 0 ? (
                                metrics.recent_alerts.map((alert, index) => (
                                    <div key={index} className="flex items-start p-4 border rounded-lg">
                                        <div className={`flex-shrink-0 w-2 h-2 mt-1.5 rounded-full ${
                                            alert.level === 'critical' ? 'bg-red-500' : 
                                            alert.level === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
                                        }`}></div>
                                        <div className="ml-4">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {alert.context?.subject || 'Alert'}
                                                </p>
                                                <span className="text-xs text-gray-500">
                                                    {new Date(alert.timestamp).toLocaleString()}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-sm text-gray-600">
                                                {alert.message}
                                            </p>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-gray-500">No recent alerts</p>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
