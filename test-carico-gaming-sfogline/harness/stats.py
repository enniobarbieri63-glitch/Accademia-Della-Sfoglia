import sys, statistics
codes={}; times=[]; sizes=[]
for l in sys.stdin:
    p=l.split()
    if len(p)<4: continue
    codes[p[1]]=codes.get(p[1],0)+1
    times.append(float(p[2])); sizes.append(int(p[3]))
times.sort()
def pct(p): return times[min(len(times)-1,int(len(times)*p))]
print(f"requests={len(times)} codes={codes}")
if times:
    print(f"avg={statistics.mean(times)*1000:.0f}ms  p50={pct(.5)*1000:.0f}ms  p90={pct(.9)*1000:.0f}ms  p99={pct(.99)*1000:.0f}ms  max={times[-1]*1000:.0f}ms")
    print(f"avg_size={statistics.mean(sizes)/1024:.1f}KB  total={sum(sizes)/1048576:.1f}MB")
