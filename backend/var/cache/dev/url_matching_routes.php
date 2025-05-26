<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/' => [[['_route' => 'dashboard_home', '_controller' => 'App\\Controller\\DashboardController::index'], null, ['GET' => 0], null, false, false, null]],
        '/health' => [[['_route' => 'health_check', '_controller' => 'App\\Controller\\DashboardController::healthCheck'], null, ['GET' => 0], null, false, false, null]],
        '/api/dashboard' => [[['_route' => 'api_dashboard', '_controller' => 'App\\Controller\\DashboardController::dashboard'], null, ['GET' => 0], null, false, false, null]],
        '/api/docker/services' => [[['_route' => 'api_docker_services', '_controller' => 'App\\Controller\\DashboardController::dockerServices'], null, ['GET' => 0], null, false, false, null]],
        '/api/docker/containers' => [[['_route' => 'api_docker_containers', '_controller' => 'App\\Controller\\DashboardController::dockerContainers'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/metrics' => [[['_route' => 'api_infrastructure_metrics', '_controller' => 'App\\Controller\\InfrastructureController::metrics'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/metrics/latest' => [[['_route' => 'api_infrastructure_metrics_latest', '_controller' => 'App\\Controller\\InfrastructureController::latestMetrics'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/metrics/summary' => [[['_route' => 'api_infrastructure_metrics_summary', '_controller' => 'App\\Controller\\InfrastructureController::metricsSummary'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/metrics/sources' => [[['_route' => 'api_infrastructure_metrics_sources', '_controller' => 'App\\Controller\\InfrastructureController::metricsSources'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/metrics/names' => [[['_route' => 'api_infrastructure_metrics_names', '_controller' => 'App\\Controller\\InfrastructureController::metricsNames'], null, ['GET' => 0], null, false, false, null]],
        '/api/infrastructure/health' => [[['_route' => 'api_infrastructure_health', '_controller' => 'App\\Controller\\InfrastructureController::health'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/api/(?'
                    .'|docker/(?'
                        .'|services/([^/]++)/(?'
                            .'|logs(*:85)'
                            .'|history(*:99)'
                        .')'
                        .'|containers/([^/]++)/logs(*:131)'
                    .')'
                    .'|github/([^/]++)/([^/]++)(?'
                        .'|/(?'
                            .'|workflows(*:180)'
                            .'|runs(*:192)'
                            .'|stats(*:205)'
                            .'|history(*:220)'
                        .')'
                        .'|(*:229)'
                    .')'
                    .'|infrastructure/metrics/chart/([^/]++)/([^/]++)(*:284)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        85 => [[['_route' => 'api_docker_service_logs', '_controller' => 'App\\Controller\\DashboardController::dockerServiceLogs'], ['serviceId'], ['GET' => 0], null, false, false, null]],
        99 => [[['_route' => 'api_docker_service_history', '_controller' => 'App\\Controller\\DashboardController::dockerServiceHistory'], ['serviceName'], ['GET' => 0], null, false, false, null]],
        131 => [[['_route' => 'api_docker_container_logs', '_controller' => 'App\\Controller\\DashboardController::dockerContainerLogs'], ['containerId'], ['GET' => 0], null, false, false, null]],
        180 => [[['_route' => 'api_github_workflows', '_controller' => 'App\\Controller\\DashboardController::githubWorkflows'], ['owner', 'repo'], ['GET' => 0], null, false, false, null]],
        192 => [[['_route' => 'api_github_workflow_runs', '_controller' => 'App\\Controller\\DashboardController::githubWorkflowRuns'], ['owner', 'repo'], ['GET' => 0], null, false, false, null]],
        205 => [[['_route' => 'api_github_pipeline_stats', '_controller' => 'App\\Controller\\DashboardController::githubPipelineStats'], ['owner', 'repo'], ['GET' => 0], null, false, false, null]],
        220 => [[['_route' => 'api_github_pipeline_history', '_controller' => 'App\\Controller\\DashboardController::githubPipelineHistory'], ['owner', 'repo'], ['GET' => 0], null, false, false, null]],
        229 => [[['_route' => 'api_github_repository', '_controller' => 'App\\Controller\\DashboardController::githubRepository'], ['owner', 'repo'], ['GET' => 0], null, false, true, null]],
        284 => [
            [['_route' => 'api_infrastructure_metrics_chart', '_controller' => 'App\\Controller\\InfrastructureController::metricsChart'], ['source', 'metricName'], ['GET' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
