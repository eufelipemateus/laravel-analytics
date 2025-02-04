<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if (config('analytics.analyticsGraph'))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
</head>
<body>
    <div class="min-h-screen bg-gray-100 text-gray-500 py-6 flex flex-col sm:py-16">
        <div class="px-4 w-full lg:px-0 sm:max-w-5xl sm:mx-auto">
            <div class="flex justify-end">
                @include('analytics::data.filter')
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @each('analytics::stats.card', $stats, 'stat')
            </div>
            @if (config('analytics.analyticsGraph'))
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <canvas id="pageViewsChart"></canvas>

               <script type="text/javascript">
                    const ctx = document.getElementById('pageViewsChart').getContext('2d');
                    const data =  {!!  @json_encode($graph)  !!};

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels:  data.labels,
                            datasets: data.datasets
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: {
                                    title: { display: true, text: 'Hour' }
                                },
                                y: {
                                    title: { display: true, text: 'Views' },
                                    beginAtZero: true
                                }
                            }
                        }
                    });            
                </script>
               
            </div>
            @endif
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @include('analytics::data.pages-card')
                @include('analytics::data.sources-card')
                @include('analytics::data.users-card')
                @include('analytics::data.devices-card')
                @each('analytics::data.utm-card', $utm, 'data')
            </div>
        </div>
    </div>

    <script>
        const filterButton = document.getElementById('filter-button');
        const filterDropdown = document.getElementById('filter-dropdown');

        filterButton.addEventListener('click', function (e) {
            e.preventDefault();

            filterDropdown.style.display = 'block';
        });

        document.addEventListener('click', function (e) {
            if (!filterButton.contains(e.target) && !filterDropdown.contains(e.target)) {
                filterDropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>
