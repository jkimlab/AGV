"""
Usage: python3 Validate.py <deschrambler_output_dir>
"""
import os
import re
import sys

# 1. DESCHRAMBLER output files required for AGV conversion
REQUIRED_FILES = {
    "SFs/block_list.txt": "SF coordinates and identifiers",
    "SFs/Conserved.Segments": "per-species coordinates for each SF",
    "Ancestor.APCF": "orientation of SFs per APCF",
    "Ancestor.ADJS": "adjacency scores of SF pairs",
    "APCF_size.txt": "APCF lengths",
    "APCFs": "per-species coordinates per APCF",
}

# 2. Coordinate format:
COORD_RE = re.compile(
    r'^(?P<species>.+)\.(?P<chr>[^.:]+):(?P<start>\d+)-(?P<end>\d+)$'
)

def parse_and_check_coord(coord, line_num, filename):
    """Validate one coordinate string. Returns a list of messages (empty if OK)."""
    msgs = []
    m = COORD_RE.match(coord.strip())
    if not m:
        msgs.append(
            f"[{filename}:L{line_num}] Invalid coordinate format: '{coord}' "
            f"(expected species.chr:start-end)"
        )
        return msgs
    start = int(m.group('start'))
    end = int(m.group('end'))
    if start > end:
        msgs.append(
            f"[{filename}:L{line_num}] Coordinate range error: "
            f"start({start}) > end({end}) in '{coord}'"
        )
    return msgs

# 3. Check file existence and emptiness
def check_files_exist(output_dir):
    errs = []
    for rel_path, desc in REQUIRED_FILES.items():
        full = os.path.join(output_dir, rel_path)
        if not os.path.exists(full):
            errs.append(f"Missing required file: {rel_path} ({desc})")
        elif os.path.getsize(full) == 0:
            errs.append(f"Required file is empty: {rel_path} ({desc})")
    return errs


# 4. Validate Conserved.Segments coordinates
#    Format: <species>.<chr>:<start>-<end> <orientation>
def check_conserved_segments(output_dir):
    errs = []
    path = os.path.join(output_dir, "Conserved.Segments")
    if not os.path.isfile(path):
        return errs  # already reported by existence check

    with open(path) as f:
        for i, line in enumerate(f, 1):
            line = line.strip()
            if not line or line.startswith('>'):
                continue
            parts = line.split()
            coord = parts[0]
            errs.extend(parse_and_check_coord(coord, i, "Conserved.Segments"))
            # orientation check
            if len(parts) >= 2 and parts[1] not in ('+', '-'):
                errs.append(
                    f"[Conserved.Segments:L{i}] Unexpected orientation: "
                    f"'{parts[1]}' (expected + or -)"
                )
    return errs


# 5. Validate Ancestor.ADJS
def check_adjs(output_dir):
    errs = []
    path = os.path.join(output_dir, "Ancestor.ADJS")
    if not os.path.isfile(path):
        return errs

    with open(path) as f:
        for i, line in enumerate(f, 1):
            line = line.strip()
            if not line:
                continue
            cols = line.split()
            if len(cols) < 3:
                errs.append(
                    f"[Ancestor.ADJS:L{i}] Too few columns: '{line}' "
                    f"(expected 3)"
                )
                continue
            try:
                score = float(cols[2])
            except ValueError:
                errs.append(
                    f"[Ancestor.ADJS:L{i}] Adjacency score is not numeric: "
                    f"'{cols[2]}'"
                )
                continue
            if not (0.0 <= score <= 1.0):
                errs.append(
                    f"[Ancestor.ADJS:L{i}] Adjacency score out of range: "
                    f"{score} (expected 0-1)"
                )
    return errs

# 6. Validate block_list.txt
def check_block_list(output_dir):
    errs = []
    path = os.path.join(output_dir, "SFs/block_list.txt")
    if not os.path.isfile(path):
        return errs

    with open(path) as f:
        for i, line in enumerate(f, 1):
            line = line.rstrip('\n')
            if not line.strip():
                continue
            cols = line.split('\t') if '\t' in line else line.split()
            if len(cols) < 5:
                errs.append(
                    f"[block_list.txt:L{i}] Too few columns: {len(cols)} "
                    f"(expected 5)"
                )
                continue
            try:
                start, end = int(cols[1]), int(cols[2])
                if start >= end:
                    errs.append(
                        f"[block_list.txt:L{i}] start({start}) >= end({end})"
                    )
            except ValueError:
                errs.append(
                    f"[block_list.txt:L{i}] start/end not integers: "
                    f"'{cols[1]}', '{cols[2]}'"
                )
            if cols[3] not in ('+', '-'):
                errs.append(
                    f"[block_list.txt:L{i}] Unexpected orientation: '{cols[3]}'"
                )
    return errs


# 7. Main
def main():
    if len(sys.argv) != 2:
        print("Usage: python validate_deschrambler_output.py "
              "<deschrambler_output_dir>")
        sys.exit(1)

    output_dir = sys.argv[1]
    if not os.path.isdir(output_dir):
        print(f"Error: directory does not exist: {output_dir}")
        sys.exit(1)
    
    all_msgs = []
    all_msgs.extend(check_files_exist(output_dir))
    all_msgs.extend(check_block_list(output_dir))
    all_msgs.extend(check_conserved_segments(output_dir))
    all_msgs.extend(check_adjs(output_dir))
    warnings = [m for m in all_msgs if 'WARNING' in m]
    errors = [m for m in all_msgs if 'WARNING' not in m]

    if warnings:
        print(f"--- {len(warnings)} warning(s) ---")
        for w in warnings:
            print("  " + w)
        print()
    if errors:
        print(f"=== Validation FAILED: {len(errors)} error(s) ===")
        for e in errors:
            print("  " + e)
        print("\nHalt DesToAGV.py and resolve the issues above.")
        sys.exit(1)
    else:
        print("=== Validation PASSED ===")
        print("All required outputs exist and coordinate formats are valid.")
        print("You can run DesToAGV.py.")
        sys.exit(0)


if __name__ == '__main__':
    main()
