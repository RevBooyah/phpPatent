#!/bin/bash

# cluster.sh - Use MAHOUT to cluster the patents.
# Got to the default directory and runs the mahout k-means clustering algorithm on the patents.
# You must install/build mahout for this to work.

# You can remove this if mahout is in your default path. UPDATE it with your correct path and mahout version
export PATH=$PATH:/Users/SCook/Documents/workspace/mahout-distribution-0.7/bin/

export NUMCLUSTERS=20
# adjust this to determine the number of initial clusters to search for.
# If you're playing around with this and other values, you can comment out the first two mahout commands below
# since they don't need to be rerun for the clustering.  Unless you change -x values (which is useful too)

# Go to the directory that contains the patent directory
cd /tmp

#Build the sequence files from the patents directory and put them in the output directory
mahout seqdirectory -i patents/ -o output -ow

# Build the sparse matrix from the sequence files. 
mahout seq2sparse -i output/ -o sparse -ow -chunk 100 -x 75 -seq -ml 50 -n 4 -nv -ng 2

# Run the kmeans clusterting
mahout kmeans -i sparse/tfidf-vectors/ -c centroids -cl -o kmeans-clusters -k $NUMCLUSTERS -ow -x 10 -dm org.apache.mahout.common.distance.CosineDistanceMeasure
# the -x is maximum number of iterations and can be modified to find the sweet spot. Same with numclusters

# Generate the clusterdump output and put it in cdump.out
mahout clusterdump -d sparse/dictionary.file-0 -dt sequencefile -i kmeans-clusters/clusters-2-final/part-r-00000 -n 20 -b 100 -o cdump.out -p kmeans-clusters/clusteredPoints/

# Generate the clusterdump CSV output and put it in cdump.csv
mahout clusterdump -d sparse/dictionary.file-0 -dt sequencefile -i kmeans-clusters/clusters-2-final/part-r-00000 -n 20 -b 100 -o cdump.csv -p kmeans-clusters/clusteredPoints/ -of CSV


## This part is for doing a similarity/recomender
#mahout rowid -i sparse/tfidf-vectors/part-r-00000 -o patent-matrix
#mahout rowsimilarity -i patent-matrix/matrix -o patent-similarity -r 197291 --similarityClassname SIMILARITY_COSINE -m 10 -ess
# -r is the number of rows resulting from the previous rowid command
#mahout seqdumper -i patent-matrix/matrix | more
# The key/id's for the seqdumper are in patent/matric/docIndex
