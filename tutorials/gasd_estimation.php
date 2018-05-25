<!DOCTYPE html>
<html lang="en">
<head>
<title>Documentation - Point Cloud Library (PCL)</title>
</head>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Globally Aligned Spatial Distribution (GASD) descriptors &#8212; PCL 0.0 documentation</title>
    <link rel="stylesheet" href="_static/sphinxdoc.css" type="text/css" />
    <link rel="stylesheet" href="_static/pygments.css" type="text/css" />
    <script type="text/javascript" src="_static/documentation_options.js"></script>
    <script type="text/javascript" src="_static/jquery.js"></script>
    <script type="text/javascript" src="_static/underscore.js"></script>
    <script type="text/javascript" src="_static/doctools.js"></script>
    <link rel="search" title="Search" href="search.php" />
<?php
define('MODX_CORE_PATH', '/var/www/pointclouds.org/core/');
define('MODX_CONFIG_KEY', 'config');

require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('web');

$snip = $modx->runSnippet("getSiteNavigation", array('id'=>5, 'phLevels'=>'sitenav.level0,sitenav.level1', 'showPageNav'=>'n'));
$chunkOutput = $modx->getChunk("site-header", array('sitenav'=>$snip));
$bodytag = str_replace("[[+showSubmenus:notempty=`", "", $chunkOutput);
$bodytag = str_replace("`]]", "", $bodytag);
echo $bodytag;
echo "\n";
?>
<div id="pagetitle">
<h1>Documentation</h1>
<a id="donate" href="http://www.openperception.org/support/"><img src="/assets/images/donate-button.png" alt="Donate to the Open Perception foundation"/></a>
</div>
<div id="page-content">

  </head><body>

    <div class="document">
      <div class="documentwrapper">
          <div class="body" role="main">
            
  <div class="section" id="globally-aligned-spatial-distribution-gasd-descriptors">
<span id="gasd-estimation"></span><h1>Globally Aligned Spatial Distribution (GASD) descriptors</h1>
<p>This document describes the Globally Aligned Spatial Distribution (<a class="reference internal" href="#gasd" id="id1">[GASD]</a>) global descriptor to be used for efficient object recognition and pose estimation.</p>
<p>GASD is based on the estimation of a reference frame for the whole point cloud that represents an object instance, which is used for aligning it with the canonical coordinate system. After that, a descriptor is computed for the aligned point cloud based on how its 3D points are spatially distributed. Such descriptor may also be extended with color distribution throughout the aligned point cloud. The global alignment transforms of matched point clouds are used for computing object pose. For more information please see <a class="reference internal" href="#gasd" id="id2">[GASD]</a>.</p>
</div>
<div class="section" id="theoretical-primer">
<h1>Theoretical primer</h1>
<p>The Globally Aligned Spatial Distribution (or GASD) global description method takes as input a 3D point cloud that represents a partial view of a given object. The first step consists in estimating a reference frame for the point cloud, which allows the computation of a transform that aligns it to the canonical coordinate system, making the descriptor pose invariant. After alignment, a shape descriptor is computed for the point cloud based on the spatial distribution of the 3D points. Color distribution along the point cloud can also be taken into account for obtaining a shape and color descriptor with a higher discriminative power. Object recognition is then performed by matching query and train descriptors of partial views. The pose of each recognized object is also computed from the alignment transforms of matched query and train partial views.</p>
<p>The reference frame is estimated using a Principal Component Analysis (PCA) approach. Given a set of 3D points <img class="math" src="_images/math/3194256a398e2ae0a767b237967076c0fe5db9da.png" alt="\boldsymbol{P_i}"/> that represents a partial view of an object, with <img class="math" src="_images/math/d73ace3c1c708ffb8c6b74de5687197008c934ee.png" alt="i\in\{1, ..., n\}"/>, the first step consists in computing their centroid <img class="math" src="_images/math/b639e9add0264bbb6fd902492924bd73412a9ddf.png" alt="\boldsymbol{\overline{P}}"/>, which is the origin of the reference frame. Then a covariance matrix <img class="math" src="_images/math/7baef4107976bca8c2073a9367b1f08ff5a8be00.png" alt="\boldsymbol{C}"/> is computed from <img class="math" src="_images/math/3194256a398e2ae0a767b237967076c0fe5db9da.png" alt="\boldsymbol{P_i}"/> and <img class="math" src="_images/math/b639e9add0264bbb6fd902492924bd73412a9ddf.png" alt="\boldsymbol{\overline{P}}"/> as follows:</p>
<div class="math">
<p><img src="_images/math/572788f2ad3f6c053229cc7be0329dafeb4ee641.png" alt="\boldsymbol{C}=\frac{1}{n}\sum_{i=1}^{n}(\boldsymbol{P_i}-\boldsymbol{\overline{P}})(\boldsymbol{P_i}-\boldsymbol{\overline{P}})^T."/></p>
</div><p>After that, the eigenvalues <img class="math" src="_images/math/ab09ea3d9c6a9eee32d16f4d0fde9af8f165379e.png" alt="\lambda_j"/> and corresponding eigenvectors <img class="math" src="_images/math/6cdb342543489b8965ff837bdb5ba9f47ceb988c.png" alt="\boldsymbol{v_j}"/> of <img class="math" src="_images/math/7baef4107976bca8c2073a9367b1f08ff5a8be00.png" alt="\boldsymbol{C}"/> are obtained, with <img class="math" src="_images/math/7d73ee015e7bd8d95838d5ca84d8f43226dd25bb.png" alt="j\in\{1, 2, 3\}"/>, such that <img class="math" src="_images/math/db9887e593e08efa6d9a5d3bd783d7eb079cf2ed.png" alt="\boldsymbol{C}\boldsymbol{v_j}=\lambda_j\boldsymbol{v_j}"/>. Considering that the eigenvalues are arranged in ascending order, the eigenvector <img class="math" src="_images/math/04efa18a7544650d18af847e2d3efd13839012f6.png" alt="\boldsymbol{v_1}"/> associated with the minimal eigenvalue is used as the <img class="math" src="_images/math/683f2dd9129a91d21aaf1c04afa6f78b39d4cb0a.png" alt="z"/> axis of the reference frame. If the angle between <img class="math" src="_images/math/04efa18a7544650d18af847e2d3efd13839012f6.png" alt="\boldsymbol{v_1}"/> and the viewing direction is in the <img class="math" src="_images/math/937430289f7a32faa2857cc537e3755d5cf12b65.png" alt="[-90^{\circ}, 90^{\circ}]"/> range, then <img class="math" src="_images/math/04efa18a7544650d18af847e2d3efd13839012f6.png" alt="\boldsymbol{v_1}"/> is negated. This ensures that the <img class="math" src="_images/math/683f2dd9129a91d21aaf1c04afa6f78b39d4cb0a.png" alt="z"/> axis always points towards the viewer. The <img class="math" src="_images/math/a59f68a4202623bb859a7093f0316bf466e6f75d.png" alt="x"/> axis of the reference frame is the eigenvector <img class="math" src="_images/math/7ff9f4a3dc5564c800c96992fc3146d3d7ac9321.png" alt="\boldsymbol{v_3}"/> associated with the maximal eigenvalue. The <img class="math" src="_images/math/276f7e256cbddeb81eee42e1efc348f3cb4ab5f8.png" alt="y"/> axis is given by <img class="math" src="_images/math/660512bd4d0b9e1c6f3350e92900ad50e025ffaf.png" alt="\boldsymbol{v_2}=\boldsymbol{v_1}\times\boldsymbol{v_3}"/>.</p>
<p>From the reference frame, it is possible to compute a transform <img class="math" src="_images/math/3b15812928cc7b9066430732cef6dfd0866f3f5e.png" alt="[\boldsymbol{R} | \boldsymbol{t}]"/> that aligns it with the canonical coordinate system. All the points <img class="math" src="_images/math/3194256a398e2ae0a767b237967076c0fe5db9da.png" alt="\boldsymbol{P_i}"/> of the partial view are then transformed with <img class="math" src="_images/math/3b15812928cc7b9066430732cef6dfd0866f3f5e.png" alt="[\boldsymbol{R} | \boldsymbol{t}]"/>, which is defined as follows:</p>
<div class="math">
<p><img src="_images/math/bea687fe87d7c2fcac0f423730996b0ecb17e6f9.png" alt="\begin{bmatrix}
\boldsymbol{R} &amp; \boldsymbol{t} \\
\boldsymbol{0} &amp; 1
\end{bmatrix}=
\begin{bmatrix}
\boldsymbol{v_3}^T &amp; -\boldsymbol{v_3}^T\boldsymbol{\overline{P}} \\
\boldsymbol{v_2}^T &amp; -\boldsymbol{v_2}^T\boldsymbol{\overline{P}} \\
\boldsymbol{v_1}^T &amp; -\boldsymbol{v_1}^T\boldsymbol{\overline{P}} \\
\boldsymbol{0} &amp; 1
\end{bmatrix}."/></p>
</div><p>Once the point cloud is aligned using the reference frame, a pose invariant global shape descriptor can be computed from it. The point cloud axis-aligned bounding cube centered on the origin is divided into an <img class="math" src="_images/math/b374784b58a2fc34e22d1c17f49e19319144c3f3.png" alt="m_s \times m_s \times m_s"/> regular grid. For each grid cell, a histogram with <img class="math" src="_images/math/d3f74a41c119b0a003af25b539e856f7d5e6f8d3.png" alt="l_s"/> bins is computed. If <img class="math" src="_images/math/9dc71b62781a6f04fa7b226164eefa7c69bdf4b6.png" alt="l_s=1"/>, then each histogram bin will store the number of points that belong to its correspondent cell in the 3D regular grid. If <img class="math" src="_images/math/252b9fc8922415dc177109d9c517df75477dee45.png" alt="l_s&gt;1"/>, then for each cell it will be computed a histogram of normalized distances between each sample and the cloud centroid.</p>
<p>The contribution of each sample to the histogram is normalized with respect to the total number of points in the cloud. Optionally, interpolation may be used to distribute the value of each sample into adjacent cells, in an attempt to avoid boundary effects that may cause abrupt changes to the histogram when a sample shifts from being within one cell to another. The descriptor is then obtained by concatenating the computed histograms.</p>
<a class="reference internal image-reference" href="_images/grid.png"><img alt="_images/grid.png" src="_images/grid.png" style="width: 24%;" /></a>
<a class="reference internal image-reference" href="_images/grid_top_side_bottom_view.png"><img alt="_images/grid_top_side_bottom_view.png" src="_images/grid_top_side_bottom_view.png" style="width: 72%;" /></a>
<p>Color information can also be incorporated to the descriptor in order to increase its discriminative power. The color component of the descriptor is computed with an <img class="math" src="_images/math/f7edd51ebe5e3ea8b5cb069be3dcd8482696aad4.png" alt="m_c \times m_c \times m_c"/> grid similar to the one used for the shape component, but a color histogram is generated for each cell based on the colors of the points that belong to it. Point cloud color is represented in the HSV space and the hue values are accumulated in histograms with <img class="math" src="_images/math/d03d6ab4ec409fd54265a83b4a5c509c5f8cf791.png" alt="l_c"/> bins. Similarly to shape component computation, normalization with respect to number of points is performed. Additionally, interpolation of histograms samples may also be performed. The shape and color components are concatenated, resulting in the final descriptor.</p>
<p>Query and train descriptors are matched using a nearest neighbor search approach. After that, for each matched object instance, a coarse pose is computed using the alignment transforms obtained from the reference frames of the respective query and train partial views. Given the transforms <img class="math" src="_images/math/48364bcf22cc0458eba4355c32027e71c0002e42.png" alt="[\mathbf{R_{q}} | \mathbf{t_{q}}]"/> and <img class="math" src="_images/math/508b72b741074e8138acee57c8d3e41b3de81156.png" alt="[\mathbf{R_{t}} | \mathbf{t_{t}}]"/> that align the query and train partial views, respectively, the object coarse pose <img class="math" src="_images/math/4e6a27e31e512f9c6664c72988e8ee1594ab3790.png" alt="[\mathbf{R_{c}} | \mathbf{t_{c}}]"/> is obtained by</p>
<div class="math">
<p><img src="_images/math/092fc1801c44d80f43d3463f94440bc3ebdb24a8.png" alt="\begin{bmatrix}
\mathbf{R_{c}} &amp; \mathbf{t_{c}} \\
\mathbf{0} &amp; 1
\end{bmatrix}=
{\begin{bmatrix}
\mathbf{R_{q}} &amp; \mathbf{t_{q}} \\
\mathbf{0} &amp; 1
\end{bmatrix}}^{-1}
\begin{bmatrix}
\mathbf{R_{t}} &amp; \mathbf{t_{t}} \\
\mathbf{0} &amp; 1
\end{bmatrix}."/></p>
</div><p>The coarse pose <img class="math" src="_images/math/4e6a27e31e512f9c6664c72988e8ee1594ab3790.png" alt="[\mathbf{R_{c}} | \mathbf{t_{c}}]"/> can then be refined using the Iterative Closest Point (ICP) algorithm.</p>
</div>
<div class="section" id="estimating-gasd-features">
<h1>Estimating GASD features</h1>
<p>The Globally Aligned Spatial Distribution is implemented in PCL as part of the
<a class="reference external" href="http://docs.pointclouds.org/trunk/group__features.html">pcl_features</a>
library.</p>
<p>The default values for color GASD parameters are: <img class="math" src="_images/math/be00913ece9b11d52f84b831be155b63071d21b2.png" alt="m_s=6"/> (half size of 3), <img class="math" src="_images/math/9dc71b62781a6f04fa7b226164eefa7c69bdf4b6.png" alt="l_s=1"/>, <img class="math" src="_images/math/c32eebbc4d2a876e87a7229e19e31a006d703284.png" alt="m_c=4"/> (half size of 2) and <img class="math" src="_images/math/bbc6637c4b3aa7e31189c62d1e2e3f5b0c99a467.png" alt="l_c=12"/> and no histogram interpolation (INTERP_NONE). This results in an array of 984 float values. These are stored in a <strong>pcl::GASDSignature984</strong> point type. The default values for shape only GASD parameters are: <img class="math" src="_images/math/b6dd88cfb5db6ead55fdc3359a47a1d6efc58981.png" alt="m_s=8"/> (half size of 4), <img class="math" src="_images/math/9dc71b62781a6f04fa7b226164eefa7c69bdf4b6.png" alt="l_s=1"/> and trilinear histogram interpolation (INTERP_TRILINEAR). This results in an array of 512 float values, which may be stored in a <strong>pcl::GASDSignature512</strong> point type. It is also possible to use quadrilinear histogram interpolation (INTERP_QUADRILINEAR).</p>
<p>The following code snippet will estimate a GASD shape + color descriptor for an input colored point cloud.</p>
<div class="highlight-cpp notranslate"><table class="highlighttable"><tr><td class="linenos"><div class="linenodiv"><pre> 1
 2
 3
 4
 5
 6
 7
 8
 9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27</pre></div></td><td class="code"><div class="highlight"><pre><span></span><span class="cp">#include</span> <span class="cpf">&lt;pcl/point_types.h&gt;</span><span class="cp"></span>
<span class="cp">#include</span> <span class="cpf">&lt;pcl/features/gasd.h&gt;</span><span class="cp"></span>

<span class="p">{</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZRGBA</span><span class="o">&gt;::</span><span class="n">Ptr</span> <span class="n">cloud</span> <span class="p">(</span><span class="k">new</span> <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZRGBA</span><span class="o">&gt;</span><span class="p">);</span>

  <span class="p">...</span> <span class="n">read</span><span class="p">,</span> <span class="n">pass</span> <span class="n">in</span> <span class="n">or</span> <span class="n">create</span> <span class="n">a</span> <span class="n">point</span> <span class="n">cloud</span> <span class="p">...</span>

  <span class="c1">// Create the GASD estimation class, and pass the input dataset to it</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">GASDColorEstimation</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZRGBA</span><span class="p">,</span> <span class="n">pcl</span><span class="o">::</span><span class="n">GASDSignature984</span><span class="o">&gt;</span> <span class="n">gasd</span><span class="p">;</span>
  <span class="n">gasd</span><span class="p">.</span><span class="n">setInputCloud</span> <span class="p">(</span><span class="n">cloud</span><span class="p">);</span>

  <span class="c1">// Output datasets</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">GASDSignature984</span><span class="o">&gt;</span> <span class="n">descriptor</span><span class="p">;</span>

  <span class="c1">// Compute the descriptor</span>
  <span class="n">gasd</span><span class="p">.</span><span class="n">compute</span> <span class="p">(</span><span class="n">descriptor</span><span class="p">);</span>

  <span class="c1">// Get the alignment transform</span>
  <span class="n">Eigen</span><span class="o">::</span><span class="n">Matrix4f</span> <span class="n">trans</span> <span class="o">=</span> <span class="n">gasd</span><span class="p">.</span><span class="n">getTransform</span> <span class="p">(</span><span class="n">trans</span><span class="p">);</span>

  <span class="c1">// Unpack histogram bins</span>
  <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="kt">size_t</span><span class="p">(</span> <span class="n">descriptor</span><span class="p">[</span><span class="mi">0</span><span class="p">].</span><span class="n">descriptorSize</span> <span class="p">());</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
  <span class="p">{</span>
    <span class="n">descriptor</span><span class="p">[</span><span class="mi">0</span><span class="p">].</span><span class="n">histogram</span><span class="p">[</span><span class="n">i</span><span class="p">];</span>
  <span class="p">}</span>
<span class="p">}</span>
</pre></div>
</td></tr></table></div>
<p>The following code snippet will estimate a GASD shape only descriptor for an input point cloud.</p>
<div class="highlight-cpp notranslate"><table class="highlighttable"><tr><td class="linenos"><div class="linenodiv"><pre> 1
 2
 3
 4
 5
 6
 7
 8
 9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27</pre></div></td><td class="code"><div class="highlight"><pre><span></span><span class="cp">#include</span> <span class="cpf">&lt;pcl/point_types.h&gt;</span><span class="cp"></span>
<span class="cp">#include</span> <span class="cpf">&lt;pcl/features/gasd.h&gt;</span><span class="cp"></span>

<span class="p">{</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;::</span><span class="n">Ptr</span> <span class="n">cloud</span> <span class="p">(</span><span class="k">new</span> <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;</span><span class="p">);</span>

  <span class="p">...</span> <span class="n">read</span><span class="p">,</span> <span class="n">pass</span> <span class="n">in</span> <span class="n">or</span> <span class="n">create</span> <span class="n">a</span> <span class="n">point</span> <span class="n">cloud</span> <span class="p">...</span>

  <span class="c1">// Create the GASD estimation class, and pass the input dataset to it</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">GASDEstimation</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="p">,</span> <span class="n">pcl</span><span class="o">::</span><span class="n">GASDSignature512</span><span class="o">&gt;</span> <span class="n">gasd</span><span class="p">;</span>
  <span class="n">gasd</span><span class="p">.</span><span class="n">setInputCloud</span> <span class="p">(</span><span class="n">cloud</span><span class="p">);</span>

  <span class="c1">// Output datasets</span>
  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">GASDSignature512</span><span class="o">&gt;</span> <span class="n">descriptor</span><span class="p">;</span>

  <span class="c1">// Compute the descriptor</span>
  <span class="n">gasd</span><span class="p">.</span><span class="n">compute</span> <span class="p">(</span><span class="n">descriptor</span><span class="p">);</span>

  <span class="c1">// Get the alignment transform</span>
  <span class="n">Eigen</span><span class="o">::</span><span class="n">Matrix4f</span> <span class="n">trans</span> <span class="o">=</span> <span class="n">gasd</span><span class="p">.</span><span class="n">getTransform</span> <span class="p">(</span><span class="n">trans</span><span class="p">);</span>

  <span class="c1">// Unpack histogram bins</span>
  <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="kt">size_t</span><span class="p">(</span> <span class="n">descriptor</span><span class="p">[</span><span class="mi">0</span><span class="p">].</span><span class="n">descriptorSize</span> <span class="p">());</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
  <span class="p">{</span>
    <span class="n">descriptor</span><span class="p">[</span><span class="mi">0</span><span class="p">].</span><span class="n">histogram</span><span class="p">[</span><span class="n">i</span><span class="p">];</span>
  <span class="p">}</span>
<span class="p">}</span>
</pre></div>
</td></tr></table></div>
<table class="docutils citation" frame="void" id="gasd" rules="none">
<colgroup><col class="label" /><col /></colgroup>
<tbody valign="top">
<tr><td class="label">[GASD]</td><td><em>(<a class="fn-backref" href="#id1">1</a>, <a class="fn-backref" href="#id2">2</a>)</em> <a class="reference external" href="http://www.cin.ufpe.br/~jpsml/uploads/8/2/6/7/82675770/pid4349755.pdf">http://www.cin.ufpe.br/~jpsml/uploads/8/2/6/7/82675770/pid4349755.pdf</a></td></tr>
</tbody>
</table>
<div class="admonition note">
<p class="first admonition-title">Note</p>
<p class="last">&#64;InProceedings{Lima16SIBGRAPI,
author = {Joao Paulo Lima and Veronica Teichrieb},
title = {An Efficient Global Point Cloud Descriptor for Object Recognition and Pose Estimation},
booktitle = {Proceedings of the 29th SIBGRAPI - Conference on Graphics, Patterns and Images},
year = {2016},
address = {Sao Jose dos Campos, Brazil},
month = {October}
}</p>
</div>
</div>


          </div>
      </div>
      <div class="clearer"></div>
    </div>
</div> <!-- #page-content -->

<?php
$chunkOutput = $modx->getChunk("site-footer");
echo $chunkOutput;
?>

  </body>
</html>