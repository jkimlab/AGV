# AGV
- Ancestor Genome Visualization with DESCHRAMBLER
AGV is a visualization toolkit designed to convert DESCHRAMBLER output into an interactive web interface.  
It enables intuitive exploration of reconstructed ancestral genomes through visualization.

## Requirements
* Python >= 3
* PHP 7.4.3-4ubuntu2.18

## Download
```
git clone https://github.com/jkimlab/AGV.git
cp browser {Web root path}/{Website name}
# Example: /var/www/html
```
**To access web interfaces, You have to copy the browser directory to your web root path.**   
**If you use Apache, The Web root path will be "/var/www/html".**

## Running
### Command Usage
```
usage: DesToAGV.py [-h] -p INPUT_DIR -n ANCESTOR_NAME -c CONFIG_FILE [-r NAMING_OPT] [-R NAMING_TABLE_FILE]

Make AGV data

options:
  -h, --help            show this help message and exit
  -p INPUT_DIR          Input Data Path
  -n ANCESTOR_NAME      Ancestor Name
  -c CONFIG_FILE        DESCHRAMBLER Config File Path
  -r NAMING_OPT         Renaming type option (on,off,custom) / (default=off)
  -R NAMING_TABLE_FILE  If you select custom or on option, you have to put renaming table file path
```
* Example Command
  ```
  [Naming_update_default_version]
  DesToAGV.py -p ./examples/Boreo/RACA_APCFs.300K -n Boreo -c ./examples/Boreo/RACA_APCFs.300K/SFs/config.file -r on -R ./examples/naming_table_ex.txt
  
  [Naming_update_custom_version]
  DesToAGV.py -p ./examples/Boreo/RACA_APCFs.300K -n Boreo -c ./examples/Boreo/RACA_APCFs.300K/SFs/config.file -r custom -R your_naming_table.txt
  
  [Naming_update_off]
  DesToAGV.py -p ./examples/Boreo/RACA_APCFs.300K -n Boreo -c ./examples/Boreo/RACA_APCFs.300K/SFs/config.file -r off 

  ```

## Output

All output files are generated under the current working directory using the value of ANCESTOR_NAME.
**To visualize the results in the browser, you must copy the generated folder to:**
```
browser/data/{ANCESTOR_NAME}
or
{Web_name}/data/{ANCESTOR_NAME}
```
  
1. spc_list.txt : List of all used species 
2. outg_spc_list.txt : List of species used as outgroup 
3. {Ancestor}.{Chrom}.info.txt : Information table for each ancestral chromosome mapping
  
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
 
4. {Ancestor}.{Chrom}.adjS.txt : Adjacency score table for ancestral chromosome segments
  
| Column              | Description                   |
| ------------------- | ----------------------------- |
| **Ancestor**        | Name of ancestral genome      |
| **Chrom**           | Chromosome/scaffold ID        |
| **Position**        | Genomic position of adjacency |
| **Adjacency_score** | Calculated adjacency score    |

## Result
After placing the result directory under browser/data/, access the visualization through the following URLs:  
* Default
```
http://your.host/browser
```
* In case of setting the custom website name
```
http://your.host/[Website name]
```

## Example
Online Example Page <a href="http://biweb.konkuk.ac.kr/AGV/">Example Page</a>  
  
Example web data is provided under:
* examples/ in this repository
* DESCHRAMBLER official examples:
  <a href="https://github.com/jkimlab/DESCHRAMBLER/tree/master/examples/">Example Data</a>
