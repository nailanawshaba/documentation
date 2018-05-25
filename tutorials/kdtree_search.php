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
    <title>How to use a KdTree to search &#8212; PCL 0.0 documentation</title>
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
            
  <div class="section" id="how-to-use-a-kdtree-to-search">
<span id="kdtree-search"></span><h1>How to use a KdTree to search</h1>
<p>In this tutorial we will go over how to use a KdTree for finding the K nearest neighbors of a specific point or location, and then we will also go over how to find all neighbors within some radius specified by the user (in this case random).</p>
</div>
<div class="section" id="theoretical-primer">
<h1>Theoretical primer</h1>
<p>A k-d tree, or k-dimensional tree, is a data structure used in computer science for organizing some number of points in a space with k dimensions.  It is a binary search tree with other constraints imposed on it. K-d trees are very useful for range and nearest neighbor searches.  For our purposes we will generally only be dealing with point clouds in three dimensions, so all of our k-d trees will be three-dimensional.  Each level of a k-d tree splits all children along a specific dimension, using a hyperplane that is perpendicular to the corresponding axis.  At the root of the tree all children will be split based on the first dimension (i.e. if the first dimension coordinate is less than the root it will be in the left-sub tree and if it is greater than the root it will obviously be in the right sub-tree).  Each level down in the tree divides on the next dimension, returning to the first dimension once all others have been exhausted.  They most efficient way to build a k-d tree is to use a partition method like the one Quick Sort uses to place the median point at the root and everything with a smaller one dimensional value to the left and larger to the right.  You then repeat this procedure on both the left and right sub-trees until the last trees that you are to partition are only composed of one element.</p>
<p>From <a class="reference internal" href="random_sample_consensus.php#wikipedia" id="id1">[Wikipedia]</a>:</p>
<div class="figure align-center" id="id2">
<img alt="Example of a 2-d k-d tree" src="_images/2d_kdtree.png" />
<p class="caption"><span class="caption-text">This is an example of a 2-dimensional k-d tree</span></p>
</div>
<div class="figure align-center" id="id3">
<img alt="" src="_images/nn_kdtree.gif" />
<p class="caption"><span class="caption-text">This is a demonstration of hour the Nearest-Neighbor search works.</span></p>
</div>
</div>
<div class="section" id="the-code">
<h1>The code</h1>
<p>Create a file, let’s say, <code class="docutils literal notranslate"><span class="pre">kdtree_search.cpp</span></code> in your favorite editor and place the following inside:</p>
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
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82</pre></div></td><td class="code"><div class="highlight"><pre><span></span><span class="cp">#include</span> <span class="cpf">&lt;pcl/point_cloud.h&gt;</span><span class="cp"></span>
<span class="cp">#include</span> <span class="cpf">&lt;pcl/kdtree/kdtree_flann.h&gt;</span><span class="cp"></span>

<span class="cp">#include</span> <span class="cpf">&lt;iostream&gt;</span><span class="cp"></span>
<span class="cp">#include</span> <span class="cpf">&lt;vector&gt;</span><span class="cp"></span>
<span class="cp">#include</span> <span class="cpf">&lt;ctime&gt;</span><span class="cp"></span>

<span class="kt">int</span>
<span class="nf">main</span> <span class="p">(</span><span class="kt">int</span> <span class="n">argc</span><span class="p">,</span> <span class="kt">char</span><span class="o">**</span> <span class="n">argv</span><span class="p">)</span>
<span class="p">{</span>
  <span class="n">srand</span> <span class="p">(</span><span class="n">time</span> <span class="p">(</span><span class="nb">NULL</span><span class="p">));</span>

  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;::</span><span class="n">Ptr</span> <span class="n">cloud</span> <span class="p">(</span><span class="k">new</span> <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;</span><span class="p">);</span>

  <span class="c1">// Generate pointcloud data</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">width</span> <span class="o">=</span> <span class="mi">1000</span><span class="p">;</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">height</span> <span class="o">=</span> <span class="mi">1</span><span class="p">;</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">.</span><span class="n">resize</span> <span class="p">(</span><span class="n">cloud</span><span class="o">-&gt;</span><span class="n">width</span> <span class="o">*</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">height</span><span class="p">);</span>

  <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
  <span class="p">{</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">x</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">y</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">z</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="p">}</span>

  <span class="n">pcl</span><span class="o">::</span><span class="n">KdTreeFLANN</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;</span> <span class="n">kdtree</span><span class="p">;</span>

  <span class="n">kdtree</span><span class="p">.</span><span class="n">setInputCloud</span> <span class="p">(</span><span class="n">cloud</span><span class="p">);</span>

  <span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span> <span class="n">searchPoint</span><span class="p">;</span>

  <span class="n">searchPoint</span><span class="p">.</span><span class="n">x</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="n">searchPoint</span><span class="p">.</span><span class="n">y</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="n">searchPoint</span><span class="p">.</span><span class="n">z</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>

  <span class="c1">// K nearest neighbor search</span>

  <span class="kt">int</span> <span class="n">K</span> <span class="o">=</span> <span class="mi">10</span><span class="p">;</span>

  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">int</span><span class="o">&gt;</span> <span class="n">pointIdxNKNSearch</span><span class="p">(</span><span class="n">K</span><span class="p">);</span>
  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">float</span><span class="o">&gt;</span> <span class="n">pointNKNSquaredDistance</span><span class="p">(</span><span class="n">K</span><span class="p">);</span>

  <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;K nearest neighbor search at (&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">x</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">y</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">z</span>
            <span class="o">&lt;&lt;</span> <span class="s">&quot;) with K=&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">K</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>

  <span class="k">if</span> <span class="p">(</span> <span class="n">kdtree</span><span class="p">.</span><span class="n">nearestKSearch</span> <span class="p">(</span><span class="n">searchPoint</span><span class="p">,</span> <span class="n">K</span><span class="p">,</span> <span class="n">pointIdxNKNSearch</span><span class="p">,</span> <span class="n">pointNKNSquaredDistance</span><span class="p">)</span> <span class="o">&gt;</span> <span class="mi">0</span> <span class="p">)</span>
  <span class="p">{</span>
    <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">pointIdxNKNSearch</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
      <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;    &quot;</span>  <span class="o">&lt;&lt;</span>   <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">x</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">y</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">z</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; (squared distance: &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">pointNKNSquaredDistance</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;)&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>
  <span class="p">}</span>

  <span class="c1">// Neighbors within radius search</span>

  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">int</span><span class="o">&gt;</span> <span class="n">pointIdxRadiusSearch</span><span class="p">;</span>
  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">float</span><span class="o">&gt;</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">;</span>

  <span class="kt">float</span> <span class="n">radius</span> <span class="o">=</span> <span class="mf">256.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>

  <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;Neighbors within radius search at (&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">x</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">y</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">z</span>
            <span class="o">&lt;&lt;</span> <span class="s">&quot;) with radius=&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">radius</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>


  <span class="k">if</span> <span class="p">(</span> <span class="n">kdtree</span><span class="p">.</span><span class="n">radiusSearch</span> <span class="p">(</span><span class="n">searchPoint</span><span class="p">,</span> <span class="n">radius</span><span class="p">,</span> <span class="n">pointIdxRadiusSearch</span><span class="p">,</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">)</span> <span class="o">&gt;</span> <span class="mi">0</span> <span class="p">)</span>
  <span class="p">{</span>
    <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">pointIdxRadiusSearch</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
      <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;    &quot;</span>  <span class="o">&lt;&lt;</span>   <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">x</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">y</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">z</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; (squared distance: &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;)&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>
  <span class="p">}</span>


  <span class="k">return</span> <span class="mi">0</span><span class="p">;</span>
<span class="p">}</span>
</pre></div>
</td></tr></table></div>
</div>
<div class="section" id="the-explanation">
<h1>The explanation</h1>
<p>The following code first seeds rand() with the system time and then creates and fills a PointCloud with random data.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="n">srand</span> <span class="p">(</span><span class="n">time</span> <span class="p">(</span><span class="nb">NULL</span><span class="p">));</span>

  <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;::</span><span class="n">Ptr</span> <span class="n">cloud</span> <span class="p">(</span><span class="k">new</span> <span class="n">pcl</span><span class="o">::</span><span class="n">PointCloud</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;</span><span class="p">);</span>

  <span class="c1">// Generate pointcloud data</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">width</span> <span class="o">=</span> <span class="mi">1000</span><span class="p">;</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">height</span> <span class="o">=</span> <span class="mi">1</span><span class="p">;</span>
  <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">.</span><span class="n">resize</span> <span class="p">(</span><span class="n">cloud</span><span class="o">-&gt;</span><span class="n">width</span> <span class="o">*</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">height</span><span class="p">);</span>

  <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
  <span class="p">{</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">x</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">y</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
    <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span><span class="n">i</span><span class="p">].</span><span class="n">z</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="p">}</span>
</pre></div>
</div>
<p>This next bit of code creates our kdtree object and sets our randomly created cloud as the input.  Then we create a “searchPoint” which is assigned random coordinates.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="n">pcl</span><span class="o">::</span><span class="n">KdTreeFLANN</span><span class="o">&lt;</span><span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span><span class="o">&gt;</span> <span class="n">kdtree</span><span class="p">;</span>

  <span class="n">kdtree</span><span class="p">.</span><span class="n">setInputCloud</span> <span class="p">(</span><span class="n">cloud</span><span class="p">);</span>

  <span class="n">pcl</span><span class="o">::</span><span class="n">PointXYZ</span> <span class="n">searchPoint</span><span class="p">;</span>

  <span class="n">searchPoint</span><span class="p">.</span><span class="n">x</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="n">searchPoint</span><span class="p">.</span><span class="n">y</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
  <span class="n">searchPoint</span><span class="p">.</span><span class="n">z</span> <span class="o">=</span> <span class="mf">1024.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
</pre></div>
</div>
<p>Now we create an integer (and set it equal to 10) and two vectors for storing our K nearest neighbors from the search.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="c1">// K nearest neighbor search</span>

  <span class="kt">int</span> <span class="n">K</span> <span class="o">=</span> <span class="mi">10</span><span class="p">;</span>

  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">int</span><span class="o">&gt;</span> <span class="n">pointIdxNKNSearch</span><span class="p">(</span><span class="n">K</span><span class="p">);</span>
  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">float</span><span class="o">&gt;</span> <span class="n">pointNKNSquaredDistance</span><span class="p">(</span><span class="n">K</span><span class="p">);</span>

  <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;K nearest neighbor search at (&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">x</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">y</span> 
            <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">searchPoint</span><span class="p">.</span><span class="n">z</span>
            <span class="o">&lt;&lt;</span> <span class="s">&quot;) with K=&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">K</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>
</pre></div>
</div>
<p>Assuming that our KdTree returns more than 0 closest neighbors it then prints out the locations of all 10 closest neighbors to our random “searchPoint” which have been stored in our previously created vectors.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="k">if</span> <span class="p">(</span> <span class="n">kdtree</span><span class="p">.</span><span class="n">nearestKSearch</span> <span class="p">(</span><span class="n">searchPoint</span><span class="p">,</span> <span class="n">K</span><span class="p">,</span> <span class="n">pointIdxNKNSearch</span><span class="p">,</span> <span class="n">pointNKNSquaredDistance</span><span class="p">)</span> <span class="o">&gt;</span> <span class="mi">0</span> <span class="p">)</span>
  <span class="p">{</span>
    <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">pointIdxNKNSearch</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
      <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;    &quot;</span>  <span class="o">&lt;&lt;</span>   <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">x</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">y</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxNKNSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">z</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; (squared distance: &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">pointNKNSquaredDistance</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;)&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>
  <span class="p">}</span>
</pre></div>
</div>
<p>Now our code demonstrates finding all neighbors to our given “searchPoint” within some (randomly generated) radius.  It again creates 2 vectors for storing information about our neighbors.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="c1">// Neighbors within radius search</span>

  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">int</span><span class="o">&gt;</span> <span class="n">pointIdxRadiusSearch</span><span class="p">;</span>
  <span class="n">std</span><span class="o">::</span><span class="n">vector</span><span class="o">&lt;</span><span class="kt">float</span><span class="o">&gt;</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">;</span>

  <span class="kt">float</span> <span class="n">radius</span> <span class="o">=</span> <span class="mf">256.0f</span> <span class="o">*</span> <span class="n">rand</span> <span class="p">()</span> <span class="o">/</span> <span class="p">(</span><span class="n">RAND_MAX</span> <span class="o">+</span> <span class="mf">1.0f</span><span class="p">);</span>
</pre></div>
</div>
<p>Again, like before if our KdTree returns more than 0 neighbors within the specified radius it prints out the coordinates of these points which have been stored in our vectors.</p>
<div class="highlight-cpp notranslate"><div class="highlight"><pre><span></span>  <span class="k">if</span> <span class="p">(</span> <span class="n">kdtree</span><span class="p">.</span><span class="n">radiusSearch</span> <span class="p">(</span><span class="n">searchPoint</span><span class="p">,</span> <span class="n">radius</span><span class="p">,</span> <span class="n">pointIdxRadiusSearch</span><span class="p">,</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">)</span> <span class="o">&gt;</span> <span class="mi">0</span> <span class="p">)</span>
  <span class="p">{</span>
    <span class="k">for</span> <span class="p">(</span><span class="kt">size_t</span> <span class="n">i</span> <span class="o">=</span> <span class="mi">0</span><span class="p">;</span> <span class="n">i</span> <span class="o">&lt;</span> <span class="n">pointIdxRadiusSearch</span><span class="p">.</span><span class="n">size</span> <span class="p">();</span> <span class="o">++</span><span class="n">i</span><span class="p">)</span>
      <span class="n">std</span><span class="o">::</span><span class="n">cout</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;    &quot;</span>  <span class="o">&lt;&lt;</span>   <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">x</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">y</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">cloud</span><span class="o">-&gt;</span><span class="n">points</span><span class="p">[</span> <span class="n">pointIdxRadiusSearch</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="p">].</span><span class="n">z</span> 
                <span class="o">&lt;&lt;</span> <span class="s">&quot; (squared distance: &quot;</span> <span class="o">&lt;&lt;</span> <span class="n">pointRadiusSquaredDistance</span><span class="p">[</span><span class="n">i</span><span class="p">]</span> <span class="o">&lt;&lt;</span> <span class="s">&quot;)&quot;</span> <span class="o">&lt;&lt;</span> <span class="n">std</span><span class="o">::</span><span class="n">endl</span><span class="p">;</span>
  <span class="p">}</span>
</pre></div>
</div>
</div>
<div class="section" id="compiling-and-running-the-program">
<h1>Compiling and running the program</h1>
<p>Add the following lines to your CMakeLists.txt file:</p>
<div class="highlight-cmake notranslate"><table class="highlighttable"><tr><td class="linenos"><div class="linenodiv"><pre> 1
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
12</pre></div></td><td class="code"><div class="highlight"><pre><span></span><span class="nb">cmake_minimum_required</span><span class="p">(</span><span class="s">VERSION</span> <span class="s">2.8</span> <span class="s">FATAL_ERROR</span><span class="p">)</span>

<span class="nb">project</span><span class="p">(</span><span class="s">kdtree_search</span><span class="p">)</span>

<span class="nb">find_package</span><span class="p">(</span><span class="s">PCL</span> <span class="s">1.2</span> <span class="s">REQUIRED</span><span class="p">)</span>

<span class="nb">include_directories</span><span class="p">(</span><span class="o">${</span><span class="nv">PCL_INCLUDE_DIRS</span><span class="o">}</span><span class="p">)</span>
<span class="nb">link_directories</span><span class="p">(</span><span class="o">${</span><span class="nv">PCL_LIBRARY_DIRS</span><span class="o">}</span><span class="p">)</span>
<span class="nb">add_definitions</span><span class="p">(</span><span class="o">${</span><span class="nv">PCL_DEFINITIONS</span><span class="o">}</span><span class="p">)</span>

<span class="nb">add_executable</span> <span class="p">(</span><span class="s">kdtree_search</span> <span class="s">kdtree_search.cpp</span><span class="p">)</span>
<span class="nb">target_link_libraries</span> <span class="p">(</span><span class="s">kdtree_search</span> <span class="o">${</span><span class="nv">PCL_LIBRARIES</span><span class="o">}</span><span class="p">)</span>
</pre></div>
</td></tr></table></div>
<p>After you have made the executable, you can run it. Simply do:</p>
<div class="highlight-default notranslate"><div class="highlight"><pre><span></span>$ ./kdtree_search
</pre></div>
</div>
<p>Once you have run it you should see something similar to this:</p>
<div class="highlight-default notranslate"><div class="highlight"><pre><span></span><span class="n">K</span> <span class="n">nearest</span> <span class="n">neighbor</span> <span class="n">search</span> <span class="n">at</span> <span class="p">(</span><span class="mf">455.807</span> <span class="mf">417.256</span> <span class="mf">406.502</span><span class="p">)</span> <span class="k">with</span> <span class="n">K</span><span class="o">=</span><span class="mi">10</span>
  <span class="mf">494.728</span> <span class="mf">371.875</span> <span class="mf">351.687</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">6578.99</span><span class="p">)</span>
  <span class="mf">506.066</span> <span class="mf">420.079</span> <span class="mf">478.278</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">7685.67</span><span class="p">)</span>
  <span class="mf">368.546</span> <span class="mf">427.623</span> <span class="mf">416.388</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">7819.75</span><span class="p">)</span>
  <span class="mf">474.832</span> <span class="mf">383.041</span> <span class="mf">323.293</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">8456.34</span><span class="p">)</span>
  <span class="mf">470.992</span> <span class="mf">334.084</span> <span class="mf">468.459</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">10986.9</span><span class="p">)</span>
  <span class="mf">560.884</span> <span class="mf">417.637</span> <span class="mf">364.518</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">12803.8</span><span class="p">)</span>
  <span class="mf">466.703</span> <span class="mf">475.716</span> <span class="mf">306.269</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">13582.9</span><span class="p">)</span>
  <span class="mf">456.907</span> <span class="mf">336.035</span> <span class="mf">304.529</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">16996.7</span><span class="p">)</span>
  <span class="mf">452.288</span> <span class="mf">387.943</span> <span class="mf">279.481</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">17005.9</span><span class="p">)</span>
  <span class="mf">476.642</span> <span class="mf">410.422</span> <span class="mf">268.057</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">19647.9</span><span class="p">)</span>
<span class="n">Neighbors</span> <span class="n">within</span> <span class="n">radius</span> <span class="n">search</span> <span class="n">at</span> <span class="p">(</span><span class="mf">455.807</span> <span class="mf">417.256</span> <span class="mf">406.502</span><span class="p">)</span> <span class="k">with</span> <span class="n">radius</span><span class="o">=</span><span class="mf">225.932</span>
  <span class="mf">494.728</span> <span class="mf">371.875</span> <span class="mf">351.687</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">6578.99</span><span class="p">)</span>
  <span class="mf">506.066</span> <span class="mf">420.079</span> <span class="mf">478.278</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">7685.67</span><span class="p">)</span>
  <span class="mf">368.546</span> <span class="mf">427.623</span> <span class="mf">416.388</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">7819.75</span><span class="p">)</span>
  <span class="mf">474.832</span> <span class="mf">383.041</span> <span class="mf">323.293</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">8456.34</span><span class="p">)</span>
  <span class="mf">470.992</span> <span class="mf">334.084</span> <span class="mf">468.459</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">10986.9</span><span class="p">)</span>
  <span class="mf">560.884</span> <span class="mf">417.637</span> <span class="mf">364.518</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">12803.8</span><span class="p">)</span>
  <span class="mf">466.703</span> <span class="mf">475.716</span> <span class="mf">306.269</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">13582.9</span><span class="p">)</span>
  <span class="mf">456.907</span> <span class="mf">336.035</span> <span class="mf">304.529</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">16996.7</span><span class="p">)</span>
  <span class="mf">452.288</span> <span class="mf">387.943</span> <span class="mf">279.481</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">17005.9</span><span class="p">)</span>
  <span class="mf">476.642</span> <span class="mf">410.422</span> <span class="mf">268.057</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">19647.9</span><span class="p">)</span>
  <span class="mf">499.429</span> <span class="mf">541.532</span> <span class="mf">351.35</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mi">20389</span><span class="p">)</span>
  <span class="mf">574.418</span> <span class="mf">452.961</span> <span class="mf">334.7</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">20498.9</span><span class="p">)</span>
  <span class="mf">336.785</span> <span class="mf">391.057</span> <span class="mf">488.71</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mi">21611</span><span class="p">)</span>
  <span class="mf">319.765</span> <span class="mf">406.187</span> <span class="mf">350.955</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">21715.6</span><span class="p">)</span>
  <span class="mf">528.89</span> <span class="mf">289.583</span> <span class="mf">378.979</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">22399.1</span><span class="p">)</span>
  <span class="mf">504.509</span> <span class="mf">459.609</span> <span class="mf">541.732</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">22452.8</span><span class="p">)</span>
  <span class="mf">539.854</span> <span class="mf">349.333</span> <span class="mf">300.395</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">22936.3</span><span class="p">)</span>
  <span class="mf">548.51</span> <span class="mf">458.035</span> <span class="mf">292.812</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">23182.1</span><span class="p">)</span>
  <span class="mf">546.284</span> <span class="mf">426.67</span> <span class="mf">535.989</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">25041.6</span><span class="p">)</span>
  <span class="mf">577.058</span> <span class="mf">390.276</span> <span class="mf">508.597</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">25853.1</span><span class="p">)</span>
  <span class="mf">543.16</span> <span class="mf">458.727</span> <span class="mf">276.859</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">26157.5</span><span class="p">)</span>
  <span class="mf">613.997</span> <span class="mf">387.397</span> <span class="mf">443.207</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">27262.7</span><span class="p">)</span>
  <span class="mf">608.235</span> <span class="mf">467.363</span> <span class="mf">327.264</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">32023.6</span><span class="p">)</span>
  <span class="mf">506.842</span> <span class="mf">591.736</span> <span class="mf">391.923</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">33260.3</span><span class="p">)</span>
  <span class="mf">529.842</span> <span class="mf">475.715</span> <span class="mf">241.532</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">36113.7</span><span class="p">)</span>
  <span class="mf">485.822</span> <span class="mf">322.623</span> <span class="mf">244.347</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">36150.5</span><span class="p">)</span>
  <span class="mf">362.036</span> <span class="mf">318.014</span> <span class="mf">269.201</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">37493.6</span><span class="p">)</span>
  <span class="mf">493.806</span> <span class="mf">600.083</span> <span class="mf">462.742</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">38032.3</span><span class="p">)</span>
  <span class="mf">392.315</span> <span class="mf">368.085</span> <span class="mf">585.37</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">38442.9</span><span class="p">)</span>
  <span class="mf">303.826</span> <span class="mf">428.659</span> <span class="mf">533.642</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">39392.8</span><span class="p">)</span>
  <span class="mf">616.492</span> <span class="mf">424.551</span> <span class="mf">289.524</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">39556.8</span><span class="p">)</span>
  <span class="mf">320.563</span> <span class="mf">333.216</span> <span class="mf">278.242</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">41804.5</span><span class="p">)</span>
  <span class="mf">646.599</span> <span class="mf">502.256</span> <span class="mf">424.46</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">43948.8</span><span class="p">)</span>
  <span class="mf">556.202</span> <span class="mf">325.013</span> <span class="mf">568.252</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mi">44751</span><span class="p">)</span>
  <span class="mf">291.27</span> <span class="mf">497.352</span> <span class="mf">515.938</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">45463.9</span><span class="p">)</span>
  <span class="mf">286.483</span> <span class="mf">322.401</span> <span class="mf">495.377</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">45567.2</span><span class="p">)</span>
  <span class="mf">367.288</span> <span class="mf">550.421</span> <span class="mf">550.551</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">46318.6</span><span class="p">)</span>
  <span class="mf">595.122</span> <span class="mf">582.77</span> <span class="mf">394.894</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">46938.1</span><span class="p">)</span>
  <span class="mf">256.784</span> <span class="mf">499.401</span> <span class="mf">379.931</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">47064.1</span><span class="p">)</span>
  <span class="mf">430.782</span> <span class="mf">230.854</span> <span class="mf">293.829</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">48067.2</span><span class="p">)</span>
  <span class="mf">261.051</span> <span class="mf">486.593</span> <span class="mf">329.854</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">48612.7</span><span class="p">)</span>
  <span class="mf">602.061</span> <span class="mf">327.892</span> <span class="mf">545.269</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">48632.4</span><span class="p">)</span>
  <span class="mf">347.074</span> <span class="mf">610.994</span> <span class="mf">395.622</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">49475.6</span><span class="p">)</span>
  <span class="mf">482.876</span> <span class="mf">284.894</span> <span class="mf">583.888</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">49718.6</span><span class="p">)</span>
  <span class="mf">356.962</span> <span class="mf">247.285</span> <span class="mf">514.959</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">50423.7</span><span class="p">)</span>
  <span class="mf">282.065</span> <span class="mf">509.488</span> <span class="mf">516.216</span> <span class="p">(</span><span class="n">squared</span> <span class="n">distance</span><span class="p">:</span> <span class="mf">50730.4</span><span class="p">)</span>
</pre></div>
</div>
<table class="docutils citation" frame="void" id="wikipedia" rules="none">
<colgroup><col class="label" /><col /></colgroup>
<tbody valign="top">
<tr><td class="label"><a class="fn-backref" href="#id1">[Wikipedia]</a></td><td><a class="reference external" href="http://en.wikipedia.org/wiki/K-d_tree">http://en.wikipedia.org/wiki/K-d_tree</a></td></tr>
</tbody>
</table>
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