<?php
$session = \Config\Services::session();
$usession = $session->get('sup_username');
?>
<style>
#org-chart-container {
    width: 100%;
    overflow: auto;
    min-height: 500px;
    position: relative;
}
#org-chart-container svg {
    display: block;
    margin: 0 auto;
}
.org-node rect {
    fill: #fff;
    stroke: #4e73df;
    stroke-width: 1.5px;
    rx: 6;
    ry: 6;
    cursor: pointer;
}
.org-node rect.dept-node {
    fill: #f0f4ff;
    stroke: #4e73df;
}
.org-node rect.root-node {
    fill: #4e73df;
    stroke: #2e59d9;
}
.org-node text {
    font-family: 'Nunito', sans-serif;
    font-size: 11px;
    fill: #333;
}
.org-node text.root-text {
    fill: #fff;
    font-weight: 700;
}
.org-node text.node-name {
    font-weight: 600;
    font-size: 12px;
}
.org-node text.node-designation {
    font-size: 10px;
    fill: #666;
}
.org-link {
    fill: none;
    stroke: #ccc;
    stroke-width: 1.5px;
}
.org-node .toggle-circle {
    fill: #4e73df;
    stroke: #fff;
    stroke-width: 1.5px;
    cursor: pointer;
}
.org-node .toggle-text {
    fill: #fff;
    font-size: 10px;
    font-weight: 700;
    text-anchor: middle;
    dominant-baseline: central;
    pointer-events: none;
}
@media print {
    .no-print { display: none !important; }
    #org-chart-container { overflow: visible; }
    #org-chart-container svg { width: 100% !important; height: auto !important; }
}
</style>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card text-left">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?= lang('Dashboard.xin_org_chart_title');?></h5>
                <button class="btn btn-sm btn-outline-primary no-print" onclick="window.print()">
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </button>
            </div>
            <div class="card-body">
                <div id="org-chart-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(function() {
    const container = document.getElementById('org-chart-container');
    const margin = {top: 40, right: 120, bottom: 40, left: 120};
    const nodeWidth = 180;
    const nodeHeight = 60;

    d3.json('<?= site_url("erp/org-chart/data/"); ?>').then(function(treeData) {
        container.innerHTML = '';

        const root = d3.hierarchy(treeData);
        root.x0 = 0;
        root.y0 = 0;

        // Collapse children after level 2 initially
        if (root.children) {
            root.children.forEach(function(d) {
                if (d.children && d.depth >= 2) {
                    d._children = d.children;
                    d.children = null;
                }
            });
        }

        const treeLayout = d3.tree().nodeSize([nodeWidth + 20, nodeHeight + 60]);

        function update(source) {
            const treeDataLayout = treeLayout(root);
            const nodes = treeDataLayout.descendants();
            const links = treeDataLayout.links();

            // Compute bounding box
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            nodes.forEach(function(d) {
                if (d.x < minX) minX = d.x;
                if (d.x > maxX) maxX = d.x;
                if (d.y < minY) minY = d.y;
                if (d.y > maxY) maxY = d.y;
            });

            const width = (maxX - minX) + nodeWidth + margin.left + margin.right;
            const height = (maxY - minY) + nodeHeight + margin.top + margin.bottom;

            // Remove old SVG and create new
            d3.select(container).select('svg').remove();
            const svg = d3.select(container).append('svg')
                .attr('width', Math.max(width, container.clientWidth))
                .attr('height', height + 40);

            const g = svg.append('g')
                .attr('transform', 'translate(' + (margin.left + (-minX) + nodeWidth/2) + ',' + margin.top + ')');

            // Links
            const link = g.selectAll('.org-link')
                .data(links)
                .enter().append('path')
                .attr('class', 'org-link')
                .attr('d', function(d) {
                    return 'M' + d.source.x + ',' + (d.source.y + nodeHeight) +
                           'C' + d.source.x + ',' + (d.source.y + nodeHeight + 30) +
                           ' ' + d.target.x + ',' + (d.target.y - 30) +
                           ' ' + d.target.x + ',' + d.target.y;
                });

            // Nodes
            const node = g.selectAll('.org-node')
                .data(nodes)
                .enter().append('g')
                .attr('class', 'org-node')
                .attr('transform', function(d) { return 'translate(' + (d.x - nodeWidth/2) + ',' + d.y + ')'; });

            // Node rectangles
            node.append('rect')
                .attr('width', nodeWidth)
                .attr('height', nodeHeight)
                .attr('class', function(d) {
                    if (d.depth === 0) return 'root-node';
                    if (d.depth === 1) return 'dept-node';
                    return '';
                })
                .on('click', function(event, d) {
                    toggleNode(d);
                    update(d);
                });

            // Profile photos (clip to circle)
            const photoNodes = node.filter(function(d) { return d.data.photo_url && d.data.user_id > 0; });

            photoNodes.append('clipPath')
                .attr('id', function(d, i) { return 'clip-' + i; })
                .append('circle')
                .attr('cx', 25)
                .attr('cy', nodeHeight/2)
                .attr('r', 18);

            photoNodes.append('image')
                .attr('xlink:href', function(d) { return d.data.photo_url; })
                .attr('x', 7)
                .attr('y', nodeHeight/2 - 18)
                .attr('width', 36)
                .attr('height', 36)
                .attr('clip-path', function(d, i) { return 'url(#clip-' + i + ')'; })
                .on('error', function() {
                    d3.select(this).attr('xlink:href', '<?= base_url("assets/images/avatar.png"); ?>');
                });

            // Photo circle border
            photoNodes.append('circle')
                .attr('cx', 25)
                .attr('cy', nodeHeight/2)
                .attr('r', 18)
                .attr('fill', 'none')
                .attr('stroke', '#ddd')
                .attr('stroke-width', 1.5);

            // Name text
            node.append('text')
                .attr('class', function(d) {
                    return d.depth === 0 ? 'node-name root-text' : 'node-name';
                })
                .attr('x', function(d) { return (d.data.photo_url && d.data.user_id > 0) ? 50 : 10; })
                .attr('y', nodeHeight/2 - 5)
                .text(function(d) {
                    var name = d.data.name || '';
                    return name.length > 18 ? name.substring(0, 16) + '..' : name;
                });

            // Designation text
            node.append('text')
                .attr('class', function(d) {
                    return d.depth === 0 ? 'node-designation root-text' : 'node-designation';
                })
                .attr('x', function(d) { return (d.data.photo_url && d.data.user_id > 0) ? 50 : 10; })
                .attr('y', nodeHeight/2 + 10)
                .text(function(d) {
                    var desig = d.data.designation || '';
                    return desig.length > 22 ? desig.substring(0, 20) + '..' : desig;
                });

            // Toggle circles for collapsible nodes
            const toggleable = node.filter(function(d) { return d.children || d._children; });

            toggleable.append('circle')
                .attr('class', 'toggle-circle')
                .attr('cx', nodeWidth / 2)
                .attr('cy', nodeHeight + 8)
                .attr('r', 8)
                .on('click', function(event, d) {
                    event.stopPropagation();
                    toggleNode(d);
                    update(d);
                });

            toggleable.append('text')
                .attr('class', 'toggle-text')
                .attr('x', nodeWidth / 2)
                .attr('y', nodeHeight + 8)
                .text(function(d) { return d.children ? '-' : '+'; });
        }

        function toggleNode(d) {
            if (d.children) {
                d._children = d.children;
                d.children = null;
            } else if (d._children) {
                d.children = d._children;
                d._children = null;
            }
        }

        update(root);

    }).catch(function(error) {
        container.innerHTML = '<div class="alert alert-warning text-center">Unable to load organization chart data.</div>';
        console.error('Org chart error:', error);
    });
})();
</script>
