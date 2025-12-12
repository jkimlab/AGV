#!/usr/bin/env python3
import re
from collections import defaultdict
import os
import argparse

parser=argparse.ArgumentParser(description="Make AGV data")
parser.add_argument('-p',metavar='INPUT_DIR', help='Input Data Path',required=True)
parser.add_argument('-n',metavar='ANCESTOR_NAME',help='Ancestor Name',required=True)
parser.add_argument('-c',metavar='CONFIG_FILE',help='DESCHRAMBLER Config File Path',required=True)
parser.add_argument('-t',metavar='TREE_FILE',help='Tree File path',required=True)
parser.add_argument('-r',metavar='NAMING_OPT',default='off',choices=['on','off','custom'],help='Renaming type option (on,off,custom) / (default=off)')
parser.add_argument('-R',metavar='NAMING_TABLE_FILE',help='If you select custom or on option, you have to put renaming table file path')

args=parser.parse_args()
if args.r in ('on', 'custom') and args.R is None:
    parser.error("option -R/--rename-table is required when -r is 'on' or 'custom'")
#Parameter
dirPos=args.p #Program_path(DESC_Output)
os.system("mkdir -p result")
OutputPos="result" #Output_path -> data fix 
AncestorName=args.n #Project_Name,Ancestor_Name
Config=args.c
Tree=args.t
naming_opt=args.r #on,off,custom
rename_table=args.R
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
        match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+([+-])(?:\s+\[\d+\])?", line)
        if match:
            genome, chr_name, start, end, strand= match.groups()
            frag_id = int(frag_id)
            genome_names.add(genome)
            frag_map[frag_id][genome] = (chr_name, int(start), int(end),strand)
flag=0
Ingroup=[]
Outgroup=[]
Species={}
#Config - Outgroup
#Config - Chr level, Scaffold level
for line in open(Config):
    if '>species' in line:
        flag=1
    elif flag==1:
        if line.startswith('>')or len(line.split())!=3:break
        spc,distance,chromLevel=line.split()
        distance=int(distance)
        chromLevel=int(chromLevel)
        if distance==1 or distance==0:
            Ingroup.append(spc)
        elif distance==2:
            Outgroup.append(spc)
        if chromLevel==1:
            Species[spc]='chrom'
        else:
            Species[spc]='scaf'
#Naming
os.system(f"cp {dirPos}/SFs/ingroup.txt {OutputPos}/{AncestorName}/spc_list_temp.txt")
os.system(f"cp {dirPos}/SFs/outgroup.txt {OutputPos}/{AncestorName}/outg_spc_list_temp.txt")
os.system(f"cat {dirPos}/SFs/outgroup.txt >> {OutputPos}/{AncestorName}/spc_list_temp.txt")
if naming_opt=="on":
    naming={}
    for line in open(f"{rename_table}"):
        line=line.rstrip()
        pre,new=line.split('\t')
        naming[pre]=new
    with open (f"{OutputPos}/{AncestorName}/spc_list.txt",'w') as f:
        for line in open(f"{OutputPos}/{AncestorName}/spc_list_temp.txt"):
            line=line.rstrip()
            if line in naming.keys():
                f.write(naming[line])
                f.write('\n')
            else:
                f.write(line)
                f.write('\n')
    with open (f"{OutputPos}/{AncestorName}/outg_spc_list.txt",'w') as f:
        for line in open(f"{OutputPos}/{AncestorName}/outg_spc_list_temp.txt"):
            line=line.rstrip()
            if line in naming.keys():
                f.write(naming[line])
                f.write('\n')
            else:
                f.write(line)
                f.write('\n')
elif naming_opt=="off":
    naming={}
    os.system(f"mv {OutputPos}/{AncestorName}/spc_list_temp.txt {OutputPos}/{AncestorName}/spc_list.txt")
    os.system(f"mv {OutputPos}/{AncestorName}/outg_spc_list_temp.txt {OutputPos}/{AncestorName}/outg_spc_list.txt")
elif naming_opt=="custom":
    naming={}
    for line in open(f"{rename_table}"):
        line=line.rstrip()
        pre,new=line.split('\t')
        naming[pre]=new
    with open (f"{OutputPos}/{AncestorName}/spc_list.txt",'w') as f:
        for line in open(f"{OutputPos}/{AncestorName}/spc_list_temp.txt"):
            line=line.rstrip()
            if line in naming.keys():
                f.write(naming[line])
                f.write('\n')
            else:
                f.write(line)
                f.write('\n')
    with open (f"{OutputPos}/{AncestorName}/outg_spc_list.txt",'w') as f:
        for line in open(f"{OutputPos}/{AncestorName}/outg_spc_list_temp.txt"):
            line=line.rstrip()
            if line in naming.keys():
                f.write(naming[line])
                f.write('\n')
            else:
                f.write(line)
                f.write('\n')
else:
    print("Wrong naming_option")

#APCF.size.txt
os.system(f"cp {dirPos}/APCF_size.txt {OutputPos}/{AncestorName}/APCF.sizes.txt")
genomes = sorted(genome_names)  
block_files =defaultdict(set)
map_files =defaultdict(set)
for  (block1,block2),score in adj_scores.items():
    frag1=block_to_frag.get(block1)
    frag2=block_to_frag.get(block2)
    if frag1 is None or frag2 is None or frag1 != frag2: continue
    block1=abs(block1)
    block2=abs(block2)
    d=frag_map.get(frag1)
    genome=next(iter(d))
    flag=0
    r_chr=''
    t_chr=''
    Map=f"{dirPos}/APCF_{genome}.map"
    for line in open(Map):
        line=line.rstrip()
        if line.startswith('>'):
            ID=int(line[1:])
            if ID!=block1:continue
            flag=1
        elif f'APCF' in line and flag==1:
            match = re.search(r"(\S+)\.(\S+):(\d+)-(\d+)\s+(\S+)", line)
            _, r_chr, r_start, r_end, r_strand = match.groups()
        if r_chr:
            r_start,r_end=int(r_start),int(r_end)
            #r_start,r_end,t_start,t_end=int(r_start),int(r_end),int(t_start),int(t_end)
            block_files[frag1].add(f"{AncestorName}\t{frag1}\t{r_end}\t{score}\n")
            flag=0
            continue

mapping_number=0
for ID in sorted(set(block_to_frag.values())):
    for genome in genomes:
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
                    if Species[genome]=='scaf':
                        mapping_number+=1
                        if genome not in naming.keys():
                            map_files[ID].add(f"{AncestorName}\t{r_chr}\t{r_start}\t{r_end}\t{direction}\t{genome}\ts{mapping_number}\t{t_chr}\n")
                        else:
                            map_files[ID].add(f"{AncestorName}\t{r_chr}\t{r_start}\t{r_end}\t{direction}\t{naming[genome]}\ts{mapping_number}\t{t_chr}\n")
                        continue
                    else:
                        t_chr_split=t_chr.replace("chr","")
                        t_chr_split=t_chr_split.replace("Chr","")
                        if genome not in naming.keys():
                            map_files[ID].add(f"{AncestorName}\t{r_chr}\t{r_start}\t{r_end}\t{direction}\t{genome}\t{t_chr_split}\t{t_chr}\n")
                        else:
                            map_files[ID].add(f"{AncestorName}\t{r_chr}\t{r_start}\t{r_end}\t{direction}\t{naming[genome]}\t{t_chr_split}\t{t_chr}\n")
                        continue
for chr_name,listing in block_files.items():
    lines=sorted(listing)
    with open(f"{OutputPos}/{AncestorName}/APCF.{chr_name}.adjS_temp.txt", "w") as bf:
        for i in lines:
            bf.writelines(i)
    os.system(f"sort -k3,3n {OutputPos}/{AncestorName}/APCF.{chr_name}.adjS_temp.txt > {OutputPos}/{AncestorName}/APCF.{chr_name}.adjS.txt")
for chr_name,listing in map_files.items():
    with open(f"{OutputPos}/{AncestorName}/APCF.{chr_name}.info.txt", "w") as mf:
        for i in listing:
            mf.writelines(i)
#APCF.size.txt
os.system(f"cp {dirPos}/APCF_size.txt {OutputPos}/{AncestorName}/APCF.sizes.txt")
