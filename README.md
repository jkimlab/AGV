# AGV
- Ancestor Genome Visualization with DESCHRAMBLER

## Requirements
* Python >= 3
* PHP 7.4.3-4ubuntu2.18

## Download
```
git clone https://github.com/jkimlab/AGV.git
cp browser {Web root path} (example: /var/www/html)
```
**To access web interfaces, You have to copy the browser directory to your web root path.**   
**If you use Apache, The Web root path will be "/var/www/html".**

## Running (script)
### Running Command 
```
usage: DesToAGV.py [-h] -p INPUT_DIR -n ANCESTOR_NAME -c CONFIG_FILE -t TREE_FILE [-r NAMING_OPT] [-R NAMING_TABLE_FILE]

Make AGV data

options:
  -h, --help            show this help message and exit
  -p INPUT_DIR          Input Data Path
  -n ANCESTOR_NAME      Ancestor Name
  -c CONFIG_FILE        DESCHRAMBLER Config File Path
  -t TREE_FILE          Tree File path
  -r NAMING_OPT         Renaming type option (on,off,custom) / (default=off)
  -R NAMING_TABLE_FILE  If you select custom or on option, you have to put renaming table file path
```

## Output

The result will be made the "result" directory in your current path.  
The "ANCESTOR_NAME" option creates a result file with that name within the "result" directory.  
**Ultimately, the result/{ANCESTOR_NAME} data must be copied to browser/data to enable access to the web interface.**

- spc_list.txt : List of all used species 
- outg_spc_list.txt : List of species used as outgroup 
- {Ancestor}.{Chrom}.info.txt : Information table for each ancestral chromosome mapping
  
| Column                                     | Description                                  |
| ------------------------------------------ | -------------------------------------------- |
| **Ancestor**                               | Name of ancestral genome                     |
| **Chrom**                                  | Chromosome/scaffold ID in the ancestor       |
| **Start**                                  | Start coordinate                             |
| **End**                                    | End coordinate                               |
| **Strand**                                 | Strand orientation (`+` or `-`)              |
| **Target_species**                         | Target species name                          |
| **Target_chrom (scaffold_mapping_number)** | Target chromosome/scaffold mapping index     |
| **Target_chrom (scaffold)**                | Actual scaffold/chrom name in target species |
 
- {Ancestor}.{Chrom}.adjS.txt : Adjacency score table for ancestral chromosome segments
  
| Column              | Description                   |
| ------------------- | ----------------------------- |
| **Ancestor**        | Name of ancestral genome      |
| **Chrom**           | Chromosome/scaffold ID        |
| **Position**        | Genomic position of adjacency |
| **Adjacency_score** | Calculated adjacency score    |

## Result
- Default
```
http://your.host/AGV
```

## Example

<a href="http://biweb.konkuk.ac.kr/AGV/">Example Page</a>
 * Example data in "examples"
    - DESCHRAMBLER Output Data
 * Example Web page data in "data"

<a href="https://github.com/jkimlab/DESCHRAMBLER/tree/master/examples/">Example Data</a>
 * Example data in DESCHRAMBLER github page
 It shows the "examples" in Example Page.
