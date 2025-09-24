#!/usr/bin/env python3
import re
from collections import defaultdict
import sys
import os

#Parameter
dirPos=sys.argv[1] #Program_path(DESC_Output)
OutputPos=sys.argv[2] #Output_path
AncestorName=sys.argv[3] #Project_Name,Ancestor_Name
os.makedirs(f"{OutputPos}/{AncestorName}", exist_ok=True)

#Input 
ADJS=f"{dirPos}/Ancestor.ADJS"
APCFs=f"{dirPos}/SFs/Conserved.Segments"
Ancestor_APCF=f"{dirPos}/Ancestor.APCF"
ancestor_name = "" 
block_to_frag={}
with open(Ancestor_APCF) as f:
    current_id=None
    for line in f:
        line = line.strip()
        if line.startswith("#"):
            if line.split()[2].isdigit():
                current_id=int(line.split()[2])
            else:
                current_id=line.split()[2]
        elif line and not line.startswith('>') and current_id is not None:
            for part in line.replace('$','').split():
                block_id=int(part)
                block_to_frag[block_id]=current_id

adj_scores = {} 
with open(ADJS) as f:
    for line in f:
        parts = line.strip().split()
        if len(parts) == 3:
            block1 = int(parts[0])
            block2 = int(parts[1])
            score = float(parts[2])
            adj_scores[(block1,block2)] = score

frag_map =defaultdict(dict)
genome_names = set()

with open(APCFs) as f:
    for line in f:
        line = line.strip()
        if line.startswith('>'):
            frag_id=line[1:]
        match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+(\S+)", line)
        if match:
            genome, chr_name, start, end, strand = match.groups()
            frag_id = int(frag_id)
            genome_names.add(genome)
            frag_map[frag_id][genome] = (chr_name, int(start), int(end),strand)
genomes = sorted(genome_names)  
block_files =defaultdict(list)
map_files =defaultdict(list)
for  (block1,block2),score in adj_scores.items():
    frag1=block_to_frag.get(block1)
    frag2=block_to_frag.get(block2)
    if frag1 is None or frag2 is None or frag1 != frag2: continue
    block1=abs(block1)
    block2=abs(block2)
    d=frag_map.get(frag1)
    genome=next(iter(d))
    Map=f"{dirPos}/APCF_{genome}.map"
    flag=0
    r_chr=''
    t_chr=''
    for line in open(Map):
        line=line.rstrip()
        if line.startswith('>'):
            ID=int(line[1:])
            if ID!=block1 and ID!=block2:continue
            flag=1 if ID==block1 else 2
        elif f'APCF' in line and flag==1:
            match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+(\S+)", line)
            _, r_chr, r_start, r_end, r_strand = match.groups()
        elif f'APCF' in line and flag==2:
            match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+(\S+)", line)
            _, t_chr, t_start, t_end, t_strand = match.groups()
        if r_chr and t_chr:
            r_start,r_end,t_start,t_end=int(r_start),int(r_end),int(t_start),int(t_end)
            if r_start < t_start:
                block_files[frag1].append(f"{AncestorName}\t{frag1}\t{(r_end+t_start)//2}\t{score}\n")
            elif r_start > t_start:
                block_files[frag2].append(f"{AncestorName}\t{frag1}\t{(t_end+r_start)//2}\t{score}\n")
            break

for block, ID in block_to_frag.items():
    block=abs(block)
    for genome in genomes:
        if genome not in frag_map[ID]:continue
        merged_map=f"{dirPos}/APCF_{genome}.merged.map"
        flag=0
        for line in open(merged_map):
            line=line.rstrip()
            if line.startswith('>') or len(line)==0:
                continue
            else:
                match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+(\S+)", line)
                if f'APCF.{ID}:' in line:
                    _, r_chr, r_start, r_end, r_strand = match.groups()
                    flag=1
                elif len(line)!=0 and flag==1:
                    _,t_chr,t_start,t_end,t_strand=match.groups()
                    flag=0
                    direction="1" if t_strand==r_strand else "-1"
                    if 'scaffold' in t_chr:continue
                    map_files[ID].append(f"{AncestorName}\t{r_chr}\t{r_start}\t{r_end}\t{direction}\t{genome}\t{t_chr[3:]}\n")
                    continue
for chr_name,listing in block_files.items():
    with open(f"{OutputPos}/{AncestorName}/APCF.{chr_name}.adjS.txt", "w") as bf:
        for i in listing:
            bf.writelines(i)
for chr_name,listing in map_files.items():
    with open(f"{OutputPos}/{AncestorName}/APCF.{chr_name}.info.txt", "w") as mf:
        for i in listing:
            mf.writelines(i)

#spc_list.txt
with open(f"{OutputPos}/{AncestorName}/spc_list.txt","w") as f:
    for i in genomes:
        f.writelines(i)
        f.writelines('\n')
#APCF.size.txt
os.system(f"cp {dirPos}/APCF_size.txt {OutputPos}/{AncestorName}/APCF.sizes.txt")

